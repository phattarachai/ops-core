<?php

declare(strict_types=1);

use Phattarachai\OpsCore\Alerts\ProbeLevel;
use Phattarachai\OpsCore\Alerts\ProbeReading;
use Phattarachai\OpsCore\Alerts\RuleEvaluator;
use Phattarachai\OpsCore\Alerts\RuleSpec;
use Phattarachai\OpsCore\Alerts\RuleType;
use Phattarachai\OpsCore\Alerts\Severity;
use Phattarachai\OpsCore\Tests\Support\FakeSignalReader;

beforeEach(function (): void {
    $this->now = new DateTimeImmutable('2026-10-08 12:00:00');
    $this->signals = new FakeSignalReader;
    $this->evaluator = new RuleEvaluator($this->signals);
});

it('fires a threshold rule per key at or above N within the window', function (): void {
    $this->signals
        ->emit('http.403', 'ip:1.2.3.4', $this->now->modify('-2 minutes'), times: 20)
        ->emit('http.403', 'ip:5.6.7.8', $this->now->modify('-2 minutes'), times: 19)
        ->emit('http.403', 'ip:9.9.9.9', $this->now->modify('-6 minutes'), times: 50);

    $breaches = $this->evaluator->evaluate(new RuleSpec(1, '403 burst', RuleType::Threshold, 'http.403', threshold: 20, windowSeconds: 300), $this->now);

    expect($breaches)->toHaveCount(1)
        ->and($breaches[0])->key->toBe('ip:1.2.3.4')->value->toBe(20.0);
});

it('counts distinct values when the rule asks for them', function (): void {
    foreach (['a', 'b', 'c'] as $tenant) {
        $this->signals->emit('cross_tenant', 'user:7', $this->now->modify('-1 minute'), times: 2, meta: ['tenant' => $tenant]);
    }
    $this->signals->emit('cross_tenant', 'user:8', $this->now->modify('-1 minute'), times: 9, meta: ['tenant' => 'a']);

    $rule = new RuleSpec(2, 'cross tenant', RuleType::Threshold, 'cross_tenant', threshold: 3, windowSeconds: 600, distinctField: 'tenant');

    expect(array_map(fn ($breach): string => $breach->key, $this->evaluator->evaluate($rule, $this->now)))->toBe(['user:7']);
});

it('fires a rate rule only past the minimum sample size', function (): void {
    $this->signals
        ->emit('job.failed', 'SyncJob', $this->now->modify('-1 minute'), times: 3)
        ->emit('job.run', 'SyncJob', $this->now->modify('-1 minute'), times: 10)
        ->emit('job.failed', 'RareJob', $this->now->modify('-1 minute'))
        ->emit('job.run', 'RareJob', $this->now->modify('-1 minute'));

    $rule = new RuleSpec(3, 'job failure rate', RuleType::Rate, 'job.failed', threshold: 25, denominatorSignal: 'job.run', minSamples: 5);
    $breaches = $this->evaluator->evaluate($rule, $this->now);

    expect($breaches)->toHaveCount(1)
        ->and($breaches[0])->key->toBe('SyncJob')->value->toBe(30.0);
});

it('fires an absence rule when a heartbeat is silent too long', function (): void {
    $this->signals
        ->emit('schedule.finished', 'ops:rollup', $this->now->modify('-10 minutes'))
        ->emit('schedule.finished', 'backup', $this->now->modify('-1 minute'));

    $breaches = $this->evaluator->evaluate(new RuleSpec(4, 'heartbeat', RuleType::Absence, 'schedule.finished', threshold: 300), $this->now);

    expect($breaches)->toHaveCount(1)
        ->and($breaches[0])->key->toBe('ops:rollup')->value->toBe(600.0);
});

it('fires a state rule when a probe reaches the level, with its severity', function (): void {
    $this->signals->states['probe.disk'] = [
        'web-1' => new ProbeReading(ProbeLevel::Critical, $this->now, 97.0),
        'web-2' => new ProbeReading(ProbeLevel::Warning, $this->now, 85.0),
        'web-3' => new ProbeReading(ProbeLevel::Ok, $this->now, 20.0),
    ];

    $breaches = $this->evaluator->evaluate(new RuleSpec(5, 'disk', RuleType::State, 'probe.disk', minLevel: ProbeLevel::Warning), $this->now);

    expect(array_map(fn ($breach): array => [$breach->key, $breach->severity], $breaches))
        ->toBe([['web-1', Severity::Critical], ['web-2', Severity::Warning]]);
});

it('limits a rule to its listed keys', function (): void {
    $this->signals->emit('http.429', 'user:1', $this->now)->emit('http.429', 'user:2', $this->now);

    $rule = new RuleSpec(6, '429', RuleType::Threshold, 'http.429', threshold: 1, keys: ['user:2']);

    expect($this->evaluator->evaluate($rule, $this->now->modify('+1 second')))->toHaveCount(1);
});
