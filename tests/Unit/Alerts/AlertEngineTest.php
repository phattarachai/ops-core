<?php

declare(strict_types=1);

use Phattarachai\OpsCore\Alerts\AlertEngine;
use Phattarachai\OpsCore\Alerts\IncidentNotice;
use Phattarachai\OpsCore\Alerts\IncidentStatus;
use Phattarachai\OpsCore\Alerts\NoticeKind;
use Phattarachai\OpsCore\Alerts\ProbeLevel;
use Phattarachai\OpsCore\Alerts\ProbeReading;
use Phattarachai\OpsCore\Alerts\RuleEvaluator;
use Phattarachai\OpsCore\Alerts\RuleSpec;
use Phattarachai\OpsCore\Alerts\RuleType;
use Phattarachai\OpsCore\Alerts\Severity;
use Phattarachai\OpsCore\Tests\Support\FakeSignalReader;
use Phattarachai\OpsCore\Tests\Support\InMemoryIncidentStore;

beforeEach(function (): void {
    $this->start = new DateTimeImmutable('2026-10-08 12:00:00');
    $this->signals = new FakeSignalReader;
    $this->store = new InMemoryIncidentStore;
    $this->engine = new AlertEngine(new RuleEvaluator($this->signals), $this->store);
    $this->rule = new RuleSpec(1, '429 burst', RuleType::Threshold, 'http.429', threshold: 10, windowSeconds: 300, cooldownSeconds: 900, resolveAfterSeconds: 300);
    $this->at = fn (int $minutes): DateTimeImmutable => $this->start->modify("+{$minutes} minutes");
    $this->burst = fn (int $minute, string $key = 'ip:1') => $this->signals->emit('http.429', $key, ($this->at)($minute)->modify('-30 seconds'), times: 10);
    $this->kinds = fn (array $notices): array => array_map(fn (IncidentNotice $notice): NoticeKind => $notice->kind, $notices);
});

it('opens once, stays quiet inside the cooldown, reminds after it, then resolves', function (): void {
    ($this->burst)(0);
    expect(($this->kinds)($this->engine->runRule($this->rule, ($this->at)(0))))->toBe([NoticeKind::Opened]);

    ($this->burst)(5);
    expect($this->engine->runRule($this->rule, ($this->at)(5)))->toBe([]);

    ($this->burst)(15);
    expect(($this->kinds)($this->engine->runRule($this->rule, ($this->at)(15))))->toBe([NoticeKind::Reminder]);

    expect($this->engine->runRule($this->rule, ($this->at)(19)))->toBe([])
        ->and($this->engine->runRule($this->rule, ($this->at)(21)))->toBe([]);

    $resolved = $this->engine->runRule($this->rule, ($this->at)(24));
    expect(($this->kinds)($resolved))->toBe([NoticeKind::Resolved])
        ->and($resolved[0]->incident)
        ->status->toBe(IncidentStatus::Resolved)
        ->notifications->toBe(2)
        ->peakValue->toBe(10.0)
        ->and($this->store->openFor(1))->toBe([]);
});

it('keeps one incident per key', function (): void {
    ($this->burst)(0, 'ip:1');
    ($this->burst)(0, 'ip:2');

    $notices = $this->engine->runRule($this->rule, ($this->at)(0));

    expect($notices)->toHaveCount(2)
        ->and($this->store->openFor(1))->toHaveCount(2);
});

it('notifies at once when an incident escalates', function (): void {
    $state = new RuleSpec(9, 'disk', RuleType::State, 'probe.disk');
    $this->signals->states['probe.disk'] = ['web-1' => new ProbeReading(ProbeLevel::Warning, $this->start)];
    $this->engine->runRule($state, ($this->at)(0));

    $this->signals->states['probe.disk'] = ['web-1' => new ProbeReading(ProbeLevel::Critical, $this->start)];
    $notices = $this->engine->runRule($state, ($this->at)(1));

    expect(($this->kinds)($notices))->toBe([NoticeKind::Escalated])
        ->and($notices[0]->incident->severity)->toBe(Severity::Critical);
});

it('tracks but does not notify while muted, then catches up once unmuted', function (): void {
    $muted = new RuleSpec(1, '429 burst', RuleType::Threshold, 'http.429', threshold: 10, mutedUntil: ($this->at)(3));

    ($this->burst)(0);
    expect($this->engine->runRule($muted, ($this->at)(0)))->toBe([])
        ->and($this->store->openFor(1))->toHaveCount(1);

    ($this->burst)(4);
    expect(($this->kinds)($this->engine->runRule($muted, ($this->at)(4))))->toBe([NoticeKind::Opened]);
});

it('resolves open incidents silently when a rule is switched off or out of its environment', function (): void {
    ($this->burst)(0);
    $this->engine->runRule($this->rule, ($this->at)(0));

    $productionOnly = new RuleSpec(1, '429 burst', RuleType::Threshold, 'http.429', threshold: 10, environments: ['production']);

    expect($this->engine->runRule($productionOnly, ($this->at)(1), 'local'))->toBe([])
        ->and($this->store->openFor(1))->toBe([]);
});

it('does not announce a resolve nobody was told about', function (): void {
    $muted = new RuleSpec(1, '429 burst', RuleType::Threshold, 'http.429', threshold: 10, mutedUntil: ($this->at)(60));

    ($this->burst)(0);
    $this->engine->runRule($muted, ($this->at)(0));

    expect($this->engine->runRule($this->rule, ($this->at)(10)))->toBe([]);
});

it('reminds about critical incidents only when warnings are not reminded', function (): void {
    $state = new RuleSpec(9, 'schedule', RuleType::State, 'schedule.health', cooldownSeconds: 900, remindWarnings: false);
    $reading = fn (ProbeLevel $level): array => ['task' => new ProbeReading($level, $this->start)];

    $this->signals->states['schedule.health'] = $reading(ProbeLevel::Warning);
    expect(($this->kinds)($this->engine->runRule($state, ($this->at)(0))))->toBe([NoticeKind::Opened])
        ->and($this->engine->runRule($state, ($this->at)(15)))->toBe([])
        ->and($this->engine->runRule($state, ($this->at)(60)))->toBe([]);

    $this->signals->states['schedule.health'] = $reading(ProbeLevel::Critical);
    expect(($this->kinds)($this->engine->runRule($state, ($this->at)(61))))->toBe([NoticeKind::Escalated])
        ->and(($this->kinds)($this->engine->runRule($state, ($this->at)(76))))->toBe([NoticeKind::Reminder]);

    $this->signals->states['schedule.health'] = [];
    expect(($this->kinds)($this->engine->runRule($state, ($this->at)(82))))->toBe([NoticeKind::Resolved]);
});
