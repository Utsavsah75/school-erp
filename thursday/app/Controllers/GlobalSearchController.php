<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Book;
use App\Models\ClassModel;
use App\Models\FeeType;
use App\Models\Notice;
use App\Models\ParentModel;
use App\Models\Payment;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;

/**
 * Powers the topbar Global Search box (see app/Views/layouts/app.php).
 *
 * live() is the AJAX endpoint the search box calls as the person types —
 * it returns a small, capped set of matches per module (only for modules
 * the current role can access) as JSON, for the instant dropdown.
 *
 * index() is the full results page, used when the person presses Enter or
 * "View all results" — same categories, larger per-module limit, rendered
 * as a normal page.
 *
 * Every category is gated by Auth::can($module) so the Global Search never
 * surfaces a record type the current role isn't allowed to open.
 */
class GlobalSearchController extends Controller
{
    /** Rows fetched per category for the instant dropdown. */
    private const LIVE_LIMIT = 5;

    /** Rows fetched per category on the full results page. */
    private const PAGE_LIMIT = 20;

    public function index(): void
    {
        $term = trim((string) $this->input('q', ''));
        $results = $term !== '' ? $this->searchAll($term, self::PAGE_LIMIT) : [];
        $totalCount = array_sum(array_map(static fn ($cat) => count($cat['results']), $results));

        $this->view('search/index', [
            'pageTitle'  => 'Search Results',
            'term'       => $term,
            'results'    => $results,
            'totalCount' => $totalCount,
        ]);
    }

    /** AJAX: GET /search/live?q=... — JSON for the topbar's live dropdown. */
    public function live(): void
    {
        $term = trim((string) $this->input('q', ''));
        if (mb_strlen($term) < 2) {
            $this->json(['term' => $term, 'categories' => [], 'total' => 0]);
        }

        $results = $this->searchAll($term, self::LIVE_LIMIT);
        $totalCount = array_sum(array_map(static fn ($cat) => count($cat['results']), $results));

        $this->json([
            'term'       => $term,
            'categories' => array_values($results),
            'total'      => $totalCount,
        ]);
    }

    /**
     * Runs the search against every module the current role can access and
     * returns only the categories that came back with at least one match.
     *
     * @return array<string, array{label:string, icon:string, results:array}>
     */
    private function searchAll(string $term, int $limit): array
    {
        if ($term === '') {
            return [];
        }

        $role = Auth::role();
        $categories = [];

        if (Auth::can('students')) {
            $rows = (new Student())->searchGlobal($term, $limit);
            $categories['students'] = [
                'label'   => 'Students',
                'icon'    => 'bi-mortarboard-fill',
                'results' => array_map(function ($s) {
                    $classSection = trim(($s['class_name'] ?? '') . ' ' . ($s['section_name'] ?? ''));
                    $bits = array_filter(['Adm# ' . $s['admission_number'], $classSection !== '' ? $classSection : null]);
                    return [
                        'title'    => $s['full_name'],
                        'subtitle' => implode(' · ', $bits),
                        'url'      => url('students/' . $s['id']),
                    ];
                }, $rows),
            ];
        }

        if (Auth::can('teachers')) {
            $rows = (new Teacher())->searchGlobal($term, $limit);
            $categories['teachers'] = [
                'label'   => 'Teachers',
                'icon'    => 'bi-person-workspace',
                'results' => array_map(fn ($t) => [
                    'title'    => $t['full_name'],
                    'subtitle' => implode(' · ', array_filter(['Emp# ' . $t['employee_number'], $t['phone'] ?: null])),
                    'url'      => url('teachers/' . $t['id']),
                ], $rows),
            ];
        }

        if (Auth::can('parents')) {
            $rows = (new ParentModel())->searchGlobal($term, $limit);
            $categories['parents'] = [
                'label'   => 'Parents / Guardians',
                'icon'    => 'bi-people-fill',
                'results' => array_map(fn ($p) => [
                    'title'    => $p['father_name'] ?: ($p['guardian_name'] ?: $p['mother_name']),
                    'subtitle' => implode(' · ', array_filter([$p['phone'] ?: null, $p['email'] ?: null])),
                    'url'      => url('parents/' . $p['id']),
                ], $rows),
            ];
        }

        if (Auth::can('classes')) {
            $rows = (new ClassModel())->searchGlobal($term, $limit);
            $categories['classes'] = [
                'label'   => 'Classes',
                'icon'    => 'bi-building',
                'results' => array_map(fn ($c) => [
                    'title'    => $c['name'],
                    'subtitle' => implode(' · ', array_filter(['Code ' . $c['code'], $c['teacher_name'] ?: null])),
                    'url'      => url('classes/' . $c['id']),
                ], $rows),
            ];
        }

        if (Auth::can('sections')) {
            $rows = (new Section())->searchGlobal($term, $limit);
            $categories['sections'] = [
                'label'   => 'Sections',
                'icon'    => 'bi-diagram-3-fill',
                'results' => array_map(fn ($s) => [
                    'title'    => trim(($s['class_name'] ?? '') . ' — ' . $s['name']),
                    'subtitle' => implode(' · ', array_filter(['Code ' . $s['code'], $s['room_number'] ? 'Room ' . $s['room_number'] : null])),
                    'url'      => url('sections/' . $s['id']),
                ], $rows),
            ];
        }

        if (Auth::can('subjects')) {
            $rows = (new Subject())->searchGlobal($term, $limit);
            $categories['subjects'] = [
                'label'   => 'Subjects',
                'icon'    => 'bi-journal-bookmark-fill',
                'results' => array_map(fn ($s) => [
                    'title'    => $s['name'],
                    'subtitle' => implode(' · ', array_filter(['Code ' . $s['code'], $s['class_name'] ?? null])),
                    'url'      => url('subjects'),
                ], $rows),
            ];
        }

        if (Auth::can('library')) {
            $rows = (new Book())->searchGlobal($term, $limit);
            $categories['library'] = [
                'label'   => 'Library Books',
                'icon'    => 'bi-book-half',
                'results' => array_map(fn ($b) => [
                    'title'    => $b['title'],
                    'subtitle' => implode(' · ', array_filter([$b['author_name'] ?? null, $b['isbn'] ? 'ISBN ' . $b['isbn'] : null])),
                    'url'      => url('library/books/' . $b['id']),
                ], $rows),
            ];
        }

        if (Auth::can('payments')) {
            $rows = (new Payment())->searchGlobal($term, $limit);
            $categories['payments'] = [
                'label'   => 'Payments',
                'icon'    => 'bi-receipt',
                'results' => array_map(fn ($p) => [
                    'title'    => 'Receipt #' . $p['receipt_number'],
                    'subtitle' => implode(' · ', array_filter([$p['student_name'] ?? null, format_currency($p['amount'] ?? 0)])),
                    'url'      => url('payments/receipt/' . $p['receipt_group']),
                ], $rows),
            ];
        }

        if (Auth::can('fees')) {
            $rows = (new FeeType())->searchGlobal($term, $limit);
            $categories['fee_types'] = [
                'label'   => 'Fee Types',
                'icon'    => 'bi-cash-stack',
                'results' => array_map(fn ($f) => [
                    'title'    => $f['name'],
                    'subtitle' => $f['category'] ? tr_const(FeeType::CATEGORIES, $f['category']) : '',
                    'url'      => url('fee-types'),
                ], $rows),
            ];
        }

        if (Auth::can('notices') && $role) {
            $rows = (new Notice())->searchGlobal($term, $role, $limit);
            $categories['notices'] = [
                'label'   => 'Notices',
                'icon'    => 'bi-megaphone-fill',
                'results' => array_map(fn ($n) => [
                    'title'    => $n['title'],
                    'subtitle' => mb_strimwidth(trim((string) $n['body']), 0, 70, '…'),
                    'url'      => url('notices/' . $n['id']),
                ], $rows),
            ];
        }

        // Drop empty categories entirely — the dropdown/results page only
        // needs to render sections that actually matched something.
        return array_filter($categories, static fn ($cat) => !empty($cat['results']));
    }
}
