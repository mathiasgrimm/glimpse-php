<?php

namespace GlimpseImg;

final readonly class UsageSummary
{
    /**
     * @param  array<string, int>  $byOperation  Operation counts keyed by lowercased operation name.
     */
    public function __construct(
        public UsagePeriod $period,
        public int $operations,
        public int $bytesSaved,
        public int $averageReduction,
        public array $byOperation,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): self
    {
        $period = data_get($data, 'period');
        $byOperation = data_get($data, 'by_operation');

        return new self(
            period: UsagePeriod::fromResponse(is_array($period) ? $period : []),
            operations: (int) data_get($data, 'operations'),
            bytesSaved: (int) data_get($data, 'bytes_saved'),
            averageReduction: (int) data_get($data, 'average_reduction'),
            byOperation: is_array($byOperation) ? $byOperation : [],
        );
    }
}
