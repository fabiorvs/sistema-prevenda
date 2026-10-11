<?= $this->include('partials/header') ?>
<?php
$monthDate = new DateTimeImmutable($month . '-01');
$previousMonth = $monthDate->modify('-1 month')->format('Y-m');
$nextMonth = $monthDate->modify('+1 month')->format('Y-m');
$firstWeekday = (int) $monthDate->format('N');
$daysInMonth = (int) $monthDate->format('t');
$today = date('Y-m-d');
$eventsByDay = [];
foreach ($events as $event) {
    $eventsByDay[$event['date']][] = $event;
}
$eventClass = [
    'Pagamento' => 'payment',
    'Entrega' => 'delivery',
    'Chegada ao vendedor' => 'arrival',
    'Importação' => 'import',
];
$monthNames = [1 => 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
?>
<main class="dc-content">
    <div class="dc-page-heading">
        <div>
            <h1 class="dc-page-title">Calendário</h1>
            <p class="dc-page-subtitle">Pagamentos previstos, entregas esperadas, chegadas ao vendedor e importações.</p>
        </div>
        <form method="get" action="/calendario" class="dc-calendar-month-picker">
            <label class="visually-hidden" for="month">Mês</label>
            <input class="form-control" id="month" type="month" name="month" value="<?= esc($month) ?>">
            <button class="btn btn-primary">Ir</button>
        </form>
    </div>

    <section class="card dc-calendar-card">
        <div class="card-body dc-calendar-toolbar">
            <a class="btn btn-outline-secondary btn-sm" href="/calendario?month=<?= $previousMonth ?>"><i class="bi bi-chevron-left"></i> Anterior</a>
            <div class="text-center"><h2 class="dc-panel-title mb-1"><?= $monthNames[(int) $monthDate->format('n')] ?> de <?= $monthDate->format('Y') ?></h2><span class="small text-secondary"><?= count($events) ?> compromisso(s) no mês</span></div>
            <a class="btn btn-outline-secondary btn-sm" href="/calendario?month=<?= $nextMonth ?>">Próximo <i class="bi bi-chevron-right"></i></a>
        </div>
        <div class="dc-calendar-legend px-3 pb-3">
            <span class="payment"><i></i> Pagamento</span><span class="delivery"><i></i> Entrega</span><span class="arrival"><i></i> Chegada ao vendedor</span><span class="import"><i></i> Importação</span>
        </div>
        <div class="dc-calendar-grid">
            <?php foreach (['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'] as $dayName): ?>
                <div class="dc-calendar-weekday"><?= $dayName ?></div>
            <?php endforeach ?>
            <?php for ($blank = 1; $blank < $firstWeekday; $blank++): ?>
                <div class="dc-calendar-day is-outside"></div>
            <?php endfor ?>
            <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
                <?php $date = $monthDate->format('Y-m-') . str_pad((string) $day, 2, '0', STR_PAD_LEFT); ?>
                <div class="dc-calendar-day <?= $date === $today ? 'is-today' : '' ?>">
                    <span class="dc-calendar-day-number"><?= $day ?></span>
                    <div class="dc-calendar-events">
                        <?php foreach ($eventsByDay[$date] ?? [] as $event): ?>
                            <a class="dc-calendar-event <?= $eventClass[$event['type']] ?? 'payment' ?>" href="<?= esc($event['url']) ?>" title="<?= esc($event['type'] . ': ' . $event['title']) ?>">
                                <span><?= esc($event['type']) ?></span> <?= esc($event['title']) ?>
                            </a>
                        <?php endforeach ?>
                    </div>
                </div>
            <?php endfor ?>
            <?php for ($blank = $firstWeekday + $daysInMonth; $blank <= 42; $blank++): ?>
                <div class="dc-calendar-day is-outside"></div>
            <?php endfor ?>
        </div>
    </section>
</main>
<?= $this->include('partials/footer') ?>
