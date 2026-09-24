<?php

namespace App\Data\Infrastructure\Servers;

class ServerDiscoveryData
{
    public function __construct(
        public readonly string $osName,
        public readonly string $osVersion,
        public readonly string $kernelVersion,
        public readonly string $architecture,
        public readonly int $cpuCores,
        public readonly int $totalRamMb,
        public readonly int $totalDiskGb,
        public readonly string $hostname,
        public readonly array $detectedServices = [],
        public readonly array $installedRuntimes = [],
        public readonly array $rawMetadata = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            osName: (string) ($data['os_name'] ?? 'Linux'),
            osVersion: (string) ($data['os_version'] ?? 'Unknown'),
            kernelVersion: (string) ($data['kernel_version'] ?? 'Unknown'),
            architecture: (string) ($data['architecture'] ?? 'x86_64'),
            cpuCores: max(1, (int) ($data['cpu_cores'] ?? 1)),
            totalRamMb: max(0, (int) ($data['total_ram'] ?? 0)),
            totalDiskGb: max(0, (int) ($data['total_disk'] ?? 0)),
            hostname: (string) ($data['hostname'] ?? 'localhost'),
            detectedServices: (array) ($data['detected_services'] ?? []),
            installedRuntimes: (array) ($data['installed_runtimes'] ?? []),
            rawMetadata: (array) ($data['raw_metadata'] ?? [])
        );
    }
}
