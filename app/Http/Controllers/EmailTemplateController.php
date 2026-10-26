<?php

namespace App\Http\Controllers;

use App\Models\CompanyProfile;
use App\Models\EmailTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailTemplateController extends Controller
{
    public function index(): View
    {
        $company = CompanyProfile::query()->first();

        return view('communication.templates', [
            'templates' => EmailTemplate::orderBy('document_type')->orderBy('name')->get(),
            'companyName' => $company?->trade_name ?: ($company?->name ?: 'JST Machine Tools'),
        ]);
    }

    public function update(Request $request, EmailTemplate $template): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        $template->update([
            ...$data,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('communication.templates')
            ->with('success', 'Template “'.$template->name.'” saved.');
    }
}
