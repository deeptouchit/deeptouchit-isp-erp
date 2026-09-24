<?php

namespace App\Contracts\Infrastructure\Servers;

use App\Data\Infrastructure\Servers\ServerMetricsData;
use App\Models\Server;
use App\Models\ServerMetric;

interface ServerMetricsInterface
{
    public function recordMetrics(Server $server, ServerMetricsData|array $telemetry): ServerMetric;
}
