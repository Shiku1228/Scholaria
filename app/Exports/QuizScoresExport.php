<?php

namespace App\Exports;

use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class QuizScoresExport implements FromArray, ShouldAutoSize, WithEvents, WithStyles, WithTitle
{
    private const HEADER_ROW    = 7;
    private const DATA_START    = 8;
    private const PASSING_PCT   = 75;
    private const LAST_COL      = 'J';

    private Quiz   $quiz;
    private string $teacherName;
    private int    $dataEndRow  = 7;

    public function __construct(Quiz $quiz, string $teacherName)
    {
        $this->quiz        = $quiz;
        $this->teacherName = $teacherName;
    }

    public function array(): array
    {
        $quiz     = $this->quiz;
        $course   = $quiz->course;
        $maxScore = (int) ($quiz->points ?: ($quiz->max_score ?: 100));

        // Enrolled students (active or completed only)
        $enrolledIds = Enrollment::where('course_id', $course->id)
            ->whereIn('status', ['active', 'completed'])
            ->pluck('student_id');

        $students = User::whereIn('id', $enrolledIds)
            ->orderBy('name')
            ->get();

        // Best attempt per student: highest score among submitted attempts
        $attemptMap = QuizAttempt::where('quiz_id', $quiz->id)
            ->whereNotNull('submitted_at')
            ->whereIn('student_id', $enrolledIds)
            ->get()
            ->groupBy('student_id')
            ->map(fn ($grp) => $grp->sortByDesc('score')->first());

        // ── Metadata rows (1–5) ──────────────────────────────────────────────
        $rows = [
            ['Quiz Title',    $quiz->title],
            ['Course',        trim(($course->course_number ?? '') . '  ' . ($course->title ?? ''))],
            ['Teacher',       $this->teacherName],
            ['Date Exported', now()->format('F d, Y  g:i A')],
            ['Max Score',     $maxScore . ' pts'],
            [],                                         // row 6 — blank separator
        ];

        // ── Column headers (row 7) ────────────────────────────────────────────
        $rows[] = [
            'No.',
            'Student Number',
            'Student Name',
            'Email',
            'Submission Status',
            'Date Submitted',
            'Total Score',
            'Maximum Score',
            'Percentage',
            'Remarks',
        ];

        // ── Student data rows (row 8+) ────────────────────────────────────────
        $submitted    = 0;
        $notSubmitted = 0;
        $i            = 1;

        foreach ($students as $student) {
            $attempt = $attemptMap->get($student->id);

            if ($attempt) {
                $score   = (int) $attempt->score;
                $pct     = $maxScore > 0 ? round(($score / $maxScore) * 100, 1) : 0;
                $remarks = $pct >= self::PASSING_PCT ? 'Passed' : 'Failed';
                $submitted++;

                $rows[] = [
                    $i++,
                    $student->student_number ?? '—',
                    $student->name,
                    $student->email,
                    'Submitted',
                    $attempt->submitted_at?->format('M d, Y g:i A') ?? '',
                    $score,
                    $maxScore,
                    number_format($pct, 1) . '%',
                    $remarks,
                ];
            } else {
                $notSubmitted++;

                $rows[] = [
                    $i++,
                    $student->student_number ?? '—',
                    $student->name,
                    $student->email,
                    'Not Submitted',
                    '',
                    'N/A',
                    $maxScore,
                    'N/A',
                    'Not Submitted',
                ];
            }
        }

        // Track where student data ends so styles() can apply borders only there
        $this->dataEndRow = self::DATA_START + $students->count() - 1;
        if ($students->isEmpty()) {
            $this->dataEndRow = self::HEADER_ROW; // no data rows
        }

        // ── Summary rows ──────────────────────────────────────────────────────
        $rows[] = [];
        $rows[] = ['Total Students',   $students->count()];
        $rows[] = ['Submitted',         $submitted];
        $rows[] = ['Not Submitted',     $notSubmitted];
        $rows[] = ['Passing Score',     self::PASSING_PCT . '%'];

        return $rows;
    }

    public function title(): string
    {
        return 'Quiz Scores';
    }

    public function styles(Worksheet $sheet): array
    {
        $endRow  = max($this->dataEndRow, self::DATA_START);
        $lastCol = self::LAST_COL;

        return [
            // Metadata key column: bold
            'A1:A5' => [
                'font' => ['bold' => true],
            ],

            // Header row: bold white text on navy background, centered
            self::HEADER_ROW => [
                'font' => [
                    'bold'  => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                    'size'  => 10,
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF0B2D6B'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ],

            // Data rows: thin borders and alternating row styling is handled per-cell
            'A' . self::DATA_START . ':' . $lastCol . $endRow => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['argb' => 'FFC9D7F2'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Freeze row 7 header (pane starts at first data row)
                $sheet->freezePane('A' . self::DATA_START);

                // Make quiz title value bold and larger
                $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(12);

                // Wrap text in column C (Student Name) and D (Email)
                $sheet->getStyle('C' . self::DATA_START . ':D' . $sheet->getHighestRow())
                    ->getAlignment()->setWrapText(false);

                // Column A (No.) center-aligned in data rows
                $sheet->getStyle('A' . self::DATA_START . ':A' . $sheet->getHighestRow())
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Columns G, H (scores) and I (percentage): center-aligned
                $sheet->getStyle('G' . self::DATA_START . ':I' . $sheet->getHighestRow())
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Summary bold labels
                $summaryStart = max($this->dataEndRow + 2, self::DATA_START + 1);
                $sheet->getStyle('A' . $summaryStart . ':A' . $sheet->getHighestRow())
                    ->getFont()->setBold(true);
            },
        ];
    }
}
