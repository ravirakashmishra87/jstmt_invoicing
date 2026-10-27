<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CommunicationController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProformaController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/', function () {
        $user = auth()->user();
        $homes = [
            'dashboard.view' => 'dashboard',
            'customers.view' => 'customers.index',
            'quotations.view' => 'quotations.index',
            'proformas.view' => 'proformas.index',
            'invoices.view' => 'invoices.index',
            'receipts.view' => 'receipts.index',
            'products.view' => 'products.index',
            'stock.view' => 'stock.index',
            'suppliers.view' => 'suppliers.index',
            'reports.view' => 'reports.index',
            'communication.view' => 'communication.index',
            'settings.view' => 'settings.index',
            'users.view' => 'users.index',
        ];

        foreach ($homes as $permission => $route) {
            if ($user->hasPermission($permission)) {
                return redirect()->route($route);
            }
        }

        abort(403, 'Unauthorized');
    })->name('home');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::middleware('permission:customers.create')->group(function () {
        Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    });
    Route::middleware('permission:customers.edit')->group(function () {
        Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->whereNumber('customer')->name('customers.edit');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->whereNumber('customer')->name('customers.update');
    });
    Route::middleware('permission:customers.view')->group(function () {
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->whereNumber('customer')->name('customers.show');
    });
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])
        ->middleware('permission:customers.delete')
        ->whereNumber('customer')
        ->name('customers.destroy');

    Route::middleware('permission:suppliers.create')->group(function () {
        Route::get('/suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
        Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    });
    Route::middleware('permission:suppliers.edit')->group(function () {
        Route::get('/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->whereNumber('supplier')->name('suppliers.edit');
        Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->whereNumber('supplier')->name('suppliers.update');
    });
    Route::middleware('permission:suppliers.view')->group(function () {
        Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::get('/suppliers/{supplier}', [SupplierController::class, 'show'])->whereNumber('supplier')->name('suppliers.show');
    });
    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])
        ->middleware('permission:suppliers.delete')
        ->whereNumber('supplier')
        ->name('suppliers.destroy');

    Route::middleware('permission:products.create')->group(function () {
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::post('/products/{product}/clone', [ProductController::class, 'clone'])->whereNumber('product')->name('products.clone');
    });
    Route::middleware('permission:products.edit')->group(function () {
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->whereNumber('product')->name('products.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->whereNumber('product')->name('products.update');
        Route::post('/products/{product}/serials', [ProductController::class, 'storeSerial'])->whereNumber('product')->name('products.serials.store');
        Route::delete('/products/{product}/image', [ProductController::class, 'destroyImage'])->whereNumber('product')->name('products.image.destroy');
    });
    Route::middleware('permission:products.view')->group(function () {
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/{product}', [ProductController::class, 'show'])->whereNumber('product')->name('products.show');
    });
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])
        ->middleware('permission:products.delete')
        ->whereNumber('product')
        ->name('products.destroy');

    Route::middleware('permission:quotations.create')->group(function () {
        Route::get('/quotations/create', [QuotationController::class, 'create'])->name('quotations.create');
        Route::post('/quotations', [QuotationController::class, 'store'])->name('quotations.store');
        Route::post('/quotations/{quotation}/duplicate', [QuotationController::class, 'duplicate'])->whereNumber('quotation')->name('quotations.duplicate');
    });
    Route::middleware('permission:quotations.edit')->group(function () {
        Route::get('/quotations/{quotation}/edit', [QuotationController::class, 'edit'])->whereNumber('quotation')->name('quotations.edit');
        Route::put('/quotations/{quotation}', [QuotationController::class, 'update'])->whereNumber('quotation')->name('quotations.update');
        Route::post('/quotations/{quotation}/status', [QuotationController::class, 'updateStatus'])->whereNumber('quotation')->name('quotations.status');
    });
    Route::post('/quotations/{quotation}/convert-proforma', [QuotationController::class, 'convertToProforma'])
        ->middleware('permission:proformas.create')
        ->whereNumber('quotation')
        ->name('quotations.convert-proforma');
    Route::post('/quotations/{quotation}/convert', [QuotationController::class, 'convertToInvoice'])
        ->middleware('permission:invoices.create')
        ->whereNumber('quotation')
        ->name('quotations.convert');
    Route::get('/quotations/{quotation}/pdf', [QuotationController::class, 'pdf'])
        ->middleware('permission:quotations.print')
        ->whereNumber('quotation')
        ->name('quotations.pdf');
    Route::middleware('permission:quotations.view')->group(function () {
        Route::get('/quotations', [QuotationController::class, 'index'])->name('quotations.index');
        Route::get('/quotations/{quotation}', [QuotationController::class, 'show'])->whereNumber('quotation')->name('quotations.show');
    });

    Route::middleware('permission:proformas.create')->group(function () {
        Route::get('/proformas/create', [ProformaController::class, 'create'])->name('proformas.create');
        Route::post('/proformas', [ProformaController::class, 'store'])->name('proformas.store');
    });
    Route::middleware('permission:proformas.edit')->group(function () {
        Route::get('/proformas/{proforma}/edit', [ProformaController::class, 'edit'])->whereNumber('proforma')->name('proformas.edit');
        Route::put('/proformas/{proforma}', [ProformaController::class, 'update'])->whereNumber('proforma')->name('proformas.update');
        Route::post('/proformas/{proforma}/sent', [ProformaController::class, 'markSent'])->whereNumber('proforma')->name('proformas.sent');
        Route::post('/proformas/{proforma}/cancel', [ProformaController::class, 'cancel'])->whereNumber('proforma')->name('proformas.cancel');
    });
    Route::post('/proformas/{proforma}/convert', [ProformaController::class, 'convertToInvoice'])
        ->middleware('permission:invoices.create')
        ->whereNumber('proforma')
        ->name('proformas.convert');
    Route::get('/proformas/{proforma}/pdf', [ProformaController::class, 'pdf'])
        ->middleware('permission:proformas.print')
        ->whereNumber('proforma')
        ->name('proformas.pdf');
    Route::middleware('permission:proformas.view')->group(function () {
        Route::get('/proformas', [ProformaController::class, 'index'])->name('proformas.index');
        Route::get('/proformas/{proforma}', [ProformaController::class, 'show'])->whereNumber('proforma')->name('proformas.show');
    });

    Route::middleware('permission:invoices.create')->group(function () {
        Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
        Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    });
    Route::middleware('permission:invoices.edit')->group(function () {
        Route::get('/invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->whereNumber('invoice')->name('invoices.edit');
        Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->whereNumber('invoice')->name('invoices.update');
    });
    Route::post('/invoices/{invoice}/issue', [InvoiceController::class, 'issue'])
        ->middleware('permission:invoices.approve')
        ->whereNumber('invoice')
        ->name('invoices.issue');
    Route::post('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])
        ->middleware('permission:invoices.approve')
        ->whereNumber('invoice')
        ->name('invoices.cancel');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])
        ->middleware('permission:invoices.print')
        ->whereNumber('invoice')
        ->name('invoices.pdf');
    Route::middleware('permission:invoices.view')->group(function () {
        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->whereNumber('invoice')->name('invoices.show');
    });

    Route::middleware('permission:stock.view')->group(function () {
        Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
        Route::get('/stock/ledger', [StockController::class, 'ledger'])->name('stock.ledger');
        Route::get('/stock/serials', [StockController::class, 'serials'])->name('stock.serials');
    });
    Route::middleware('permission:stock.create')->group(function () {
        Route::get('/stock/opening', [StockController::class, 'opening'])->name('stock.opening');
        Route::post('/stock/opening', [StockController::class, 'storeOpening'])->name('stock.opening.store');
    });
    Route::middleware('permission:stock.edit')->group(function () {
        Route::get('/stock/adjustment', [StockController::class, 'adjustment'])->name('stock.adjustment');
        Route::post('/stock/adjustment', [StockController::class, 'storeAdjustment'])->name('stock.adjustment.store');
    });

    Route::middleware('permission:receipts.create')->group(function () {
        Route::get('/receipts/create', [ReceiptController::class, 'create'])->name('receipts.create');
        Route::post('/receipts', [ReceiptController::class, 'store'])->name('receipts.store');
    });
    Route::middleware('permission:receipts.view')->group(function () {
        Route::get('/receipts', [ReceiptController::class, 'index'])->name('receipts.index');
        Route::get('/receipts/{receipt}', [ReceiptController::class, 'show'])->whereNumber('receipt')->name('receipts.show');
    });

    Route::middleware('permission:reports.view')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/sales-register', [ReportController::class, 'salesRegister'])->name('reports.sales');
        Route::get('/reports/stock-summary', [ReportController::class, 'stockSummary'])->name('reports.stock');
        Route::get('/reports/outstanding', [ReportController::class, 'outstanding'])->name('reports.outstanding');
        Route::get('/reports/gst-summary', [ReportController::class, 'gstSummary'])->name('reports.gst');
    });

    Route::middleware('permission:communication.view')->group(function () {
        Route::get('/communication', [CommunicationController::class, 'index'])->name('communication.index');
        Route::get('/communication/templates', [EmailTemplateController::class, 'index'])->name('communication.templates');
    });
    Route::middleware('permission:communication.create')->group(function () {
        Route::get('/communication/compose', [CommunicationController::class, 'compose'])->name('communication.compose');
        Route::get('/communication/preview', [CommunicationController::class, 'preview'])->name('communication.preview');
        Route::post('/communication/send', [CommunicationController::class, 'send'])->name('communication.send');
        Route::post('/communication/send/{type}/{id}', [CommunicationController::class, 'sendDocument'])
            ->whereIn('type', ['quotation', 'proforma', 'invoice', 'receipt'])
            ->whereNumber('id')
            ->name('communication.send-document');
    });
    Route::put('/communication/templates/{template}', [EmailTemplateController::class, 'update'])
        ->middleware('permission:communication.edit')
        ->name('communication.templates.update');

    Route::get('/settings', [SettingController::class, 'index'])
        ->middleware('permission:settings.view')
        ->name('settings.index');
    Route::middleware('permission:settings.edit')->group(function () {
        Route::put('/settings/company', [SettingController::class, 'updateCompany'])->name('settings.company');
        Route::put('/settings/smtp', [SettingController::class, 'updateSmtp'])->name('settings.smtp');
        Route::post('/settings/banks', [SettingController::class, 'storeBank'])->name('settings.banks.store');
        Route::put('/settings/banks/{bank}', [SettingController::class, 'updateBank'])->name('settings.banks.update');
        Route::delete('/settings/banks/{bank}', [SettingController::class, 'destroyBank'])->name('settings.banks.destroy');
        Route::put('/settings/series/{series}', [SettingController::class, 'updateSeries'])->name('settings.series.update');
        Route::post('/settings/terms', [SettingController::class, 'storeTerms'])->name('settings.terms.store');
        Route::put('/settings/terms/{template}', [SettingController::class, 'updateTerms'])->name('settings.terms.update');
    });

    Route::middleware('permission:users.create')->group(function () {
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
    });
    Route::middleware('permission:users.edit')->group(function () {
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->whereNumber('user')->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->whereNumber('user')->name('users.update');
        Route::post('/users/{user}/activate', [UserController::class, 'activate'])->whereNumber('user')->name('users.activate');
        Route::post('/users/{user}/deactivate', [UserController::class, 'deactivate'])->whereNumber('user')->name('users.deactivate');
    });
    Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.view')->name('users.index');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])
        ->middleware('permission:users.delete')
        ->whereNumber('user')
        ->name('users.destroy');

    Route::get('/masters', [MasterController::class, 'index'])
        ->middleware('permission:masters.view')
        ->name('masters.index');
    Route::get('/masters/{type}', [MasterController::class, 'list'])
        ->middleware('permission:masters.view')
        ->name('masters.list');
    Route::post('/masters/{type}', [MasterController::class, 'store'])
        ->middleware('permission:masters.create')
        ->name('masters.store');
    Route::put('/masters/{type}/{id}', [MasterController::class, 'update'])
        ->middleware('permission:masters.edit')
        ->whereNumber('id')
        ->name('masters.update');
    Route::delete('/masters/{type}/{id}', [MasterController::class, 'destroy'])
        ->middleware('permission:masters.delete')
        ->whereNumber('id')
        ->name('masters.destroy');
});
