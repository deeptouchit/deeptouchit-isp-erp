<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DnsTemplate;
use App\Models\DnsTemplateRecord;
use App\Models\DnsZone;
use App\Services\DNS\DnsTemplateService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DnsTemplateController extends Controller
{
    protected DnsTemplateService $templateService;

    public function __construct(DnsTemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * Display DNS Blueprint Templates console.
     */
    public function index(Request $request): Response
    {
        $this->templateService->ensureSystemTemplates();

        $templates = DnsTemplate::with('records')
            ->orderByDesc('is_default')
            ->orderByDesc('is_system')
            ->get();

        $zones = DnsZone::select('id', 'domain', 'status')->orderBy('domain')->get();

        $defaultTemplate = $templates->firstWhere('is_default', true);

        $stats = [
            'total_templates' => $templates->count(),
            'default_template_name' => $defaultTemplate ? $defaultTemplate->name : 'None',
            'system_templates_count' => $templates->where('is_system', true)->count(),
            'total_records' => DnsTemplateRecord::count(),
        ];

        return Inertia::render('Admin/DNS/Templates', [
            'templates' => $templates,
            'zones' => $zones,
            'stats' => $stats,
        ]);
    }

    /**
     * Store new custom DNS Template.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'description' => 'nullable|string',
            'is_default' => 'boolean',
            'icon' => 'nullable|string|max:50',
            'records' => 'nullable|array',
            'records.*.name' => 'required|string',
            'records.*.type' => 'required|string|in:A,AAAA,CNAME,MX,TXT,NS,SRV,CAA',
            'records.*.content' => 'required|string',
            'records.*.ttl' => 'nullable|integer',
            'records.*.priority' => 'nullable|integer',
        ]);

        $template = $this->templateService->createTemplate($validated, auth()->id());

        return redirect()->back()->with('success', "DNS Blueprint Template `{$template->name}` created successfully.");
    }

    /**
     * Update DNS Template.
     */
    public function update(Request $request, DnsTemplate $dnsTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'description' => 'nullable|string',
            'is_default' => 'boolean',
            'icon' => 'nullable|string|max:50',
            'records' => 'nullable|array',
            'records.*.name' => 'required|string',
            'records.*.type' => 'required|string|in:A,AAAA,CNAME,MX,TXT,NS,SRV,CAA',
            'records.*.content' => 'required|string',
            'records.*.ttl' => 'nullable|integer',
            'records.*.priority' => 'nullable|integer',
        ]);

        $this->templateService->updateTemplate($dnsTemplate, $validated, auth()->id());

        return redirect()->back()->with('success', "DNS Template `{$dnsTemplate->name}` updated successfully.");
    }

    /**
     * Set template as global default.
     */
    public function setDefault(DnsTemplate $dnsTemplate)
    {
        $this->templateService->setDefault($dnsTemplate);

        return redirect()->back()->with('success', "Template `{$dnsTemplate->name}` is now the global default for new zones.");
    }

    /**
     * Duplicate template.
     */
    public function duplicate(DnsTemplate $dnsTemplate)
    {
        $clone = $this->templateService->duplicateTemplate($dnsTemplate);

        return redirect()->back()->with('success', "Cloned template `{$clone->name}` created.");
    }

    /**
     * Apply template to one or multiple zones.
     */
    public function apply(Request $request, DnsTemplate $dnsTemplate)
    {
        $validated = $request->validate([
            'zone_ids' => 'required|array|min:1',
            'zone_ids.*' => 'exists:dns_zones,id',
            'overwrite' => 'boolean',
        ]);

        $res = $this->templateService->applyTemplateBulk(
            $dnsTemplate,
            $validated['zone_ids'],
            !empty($validated['overwrite'])
        );

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Delete custom template.
     */
    public function destroy(DnsTemplate $dnsTemplate)
    {
        $res = $this->templateService->deleteTemplate($dnsTemplate);

        if (!$res['success']) {
            return redirect()->back()->withErrors(['template' => $res['error']]);
        }

        return redirect()->back()->with('success', $res['message']);
    }
}
