<?php

use Illuminate\Support\Facades\Schedule;

// Archive terminal orders older than a year — runs monthly (caps §1.4).
Schedule::command('orders:archive')->monthlyOn(1, '02:00');
