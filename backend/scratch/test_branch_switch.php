<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Branch;
use Illuminate\Http\Request;

$yayasan = User::where('username', 'yayasan')->first();
if (!$yayasan) {
    die("Yayasan user not found\n");
}

echo "Yayasan default branch: " . $yayasan->branch_id . "\n";

$branch2 = Branch::where('id', '!=', $yayasan->branch_id)->first();
if (!$branch2) {
    die("Need a second branch to test\n");
}

echo "Testing branch switch to: " . $branch2->id . "\n";

$request = Request::create('/api/dashboard/summary', 'GET');
$request->headers->set('X-Branch-Id', $branch2->id);

$app->make(\Illuminate\Contracts\Http\Kernel::class)->handle($request);

// The middleware doesn't easily let us inspect the user from outside without running through the pipeline.
// But we can check if it works by simulating the middleware.
$middleware = new \App\Http\Middleware\ActiveBranchContextMiddleware();
$request->setUserResolver(fn() => $yayasan);

$middleware->handle($request, function($req) {
    echo "Inside middleware, user branch_id is: " . $req->user()->branch_id . "\n";
    return response('ok');
});

echo "After middleware, user branch_id is: " . $yayasan->branch_id . "\n";
