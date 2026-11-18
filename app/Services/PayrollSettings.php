<?php

namespace App\Services;

/**
 * Statutory payroll rates, editable by head office. The defaults follow the
 * Nigeria Tax Act 2025 (in force from January 2026) as generally published;
 * confirm them with your accountant before the first pay run.
 */
class PayrollSettings
{
    private const KEY = 'payroll_settings';

    public const DEFAULTS = [
        // Annual PAYE bands: [width of band in naira (null = the rest), rate %].
        'paye_bands' => [
            [800000, 0],
            [2200000, 15],
            [9000000, 18],
            [13000000, 21],
            [25000000, 23],
            [null, 25],
        ],
        'rent_relief_rate' => 20,        // % of annual rent paid
        'rent_relief_cap' => 500000,     // naira per year
        'pension_employee_rate' => 8,    // % of basic + housing + transport
        'pension_employer_rate' => 10,
        'nhf_rate' => 2.5,               // % of basic
        'working_days_per_month' => 22,  // for absence deductions
    ];

    public function __construct(private SettingsService $settings) {}

    public function all(): array
    {
        $stored = json_decode((string) $this->settings->get(self::KEY, ''), true);

        return array_replace(self::DEFAULTS, is_array($stored) ? $stored : []);
    }

    public function save(array $values): void
    {
        $this->settings->set(self::KEY, json_encode(array_replace($this->all(), $values)));
    }
}
