<?php

namespace Alterindonesia\ServicePattern\Console;

use App\Models\Test;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;

class CreateServiceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:service {serviceName} {--f}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make Service File extended from Alterindonesia\ServicePattern\ServiceEloquents\BaseServiceEloquent';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $rawServiceName = $this->argument('serviceName');
        $serviceName = preg_replace('/(ServiceEloquent|Service)$/', '', $rawServiceName);

        $withFiles = $this->option('f');

        // Auto-create model if it doesn't exist
        $this->generateModel($serviceName);

        $this->generateService($serviceName);

        if ($withFiles) {
            $this->generateController($serviceName);
            $this->generateRequest($serviceName);
            $this->generateResource($serviceName);
            $this->info("Service, Controller, Request, and Resource for {$serviceName} created successfully.");
        } else {
            $this->info("Service for {$serviceName} created successfully.");
        }

    }
    private function generateModel($serviceName): void
    {
        // Check if model file already exists
        $modelPath = app_path("Models/{$serviceName}.php");

        if (!file_exists($modelPath)) {
            // Use Artisan to create the model
            Artisan::call('make:model', ['name' => "Models/{$serviceName}"]);
            $this->info("Model {$serviceName} created successfully.");
        } else {
            $this->info("Model {$serviceName} already exists.");
        }
    }

    private function generateService($serviceName): void
    {
        $folder = app_path('Services');
        $filePath = $folder . "/" . $serviceName . "ServiceEloquent.php";

        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        if (file_exists($filePath)) {
            $this->error("Service file {$serviceName}ServiceEloquent already exists");
            return;
        }

        $content = $this->generateServiceContent($serviceName);
        file_put_contents($filePath, $content);
    }

    private function generateController($serviceName)
    {
        $controllerName = $serviceName . 'Controller';
        $folder = app_path('Http/Controllers');
        $filePath = $folder . "/" . $controllerName . ".php";

        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        if (file_exists($filePath)) {
            $this->error("Controller file {$controllerName} already exists");
            return;
        }

        $content = $this->generateControllerContent($controllerName, $serviceName);
        file_put_contents($filePath, $content);
    }

    private function generateRequest($serviceName)
    {
        $requestName = $serviceName . 'Request';
        $folder = app_path('Http/Requests');
        $filePath = $folder . "/" . $requestName . ".php";

        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        if (file_exists($filePath)) {
            $this->error("Request file {$requestName} already exists");
            return;
        }

        $content = $this->generateRequestContent($requestName);
        file_put_contents($filePath, $content);
    }

    private function generateResource($serviceName)
    {
        $resourceName = $serviceName . 'Resource';
        $folder = app_path('Http/Resources');
        $filePath = $folder . "/" . $resourceName . ".php";

        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        if (file_exists($filePath)) {
            $this->error("Resource file {$resourceName} already exists");
            return;
        }

        $content = $this->generateResourceContent($resourceName);
        file_put_contents($filePath, $content);
    }

    private function generateServiceContent($serviceName): string
    {
        return "<?php

namespace App\Services;

use Alterindonesia\ServicePattern\ServiceEloquents\BaseServiceEloquent;
use App\Models\\{$serviceName};

class {$serviceName}ServiceEloquent extends BaseServiceEloquent
{
    public function __construct({$serviceName} \$model)
    {
        parent::__construct(\$model);
    }
}
";
    }

    private function generateControllerContent($controllerName, $serviceName)
    {
        return "<?php

namespace App\Http\Controllers;

use Alterindonesia\ServicePattern\Contracts\IServiceEloquent;
use App\Http\Requests\\{$serviceName}Request;
use App\Http\Resources\\{$serviceName}Resource;

class {$controllerName} extends Controller
{
    public function __construct(
        IServiceEloquent \$service,
        string \$request = {$serviceName}Request::class,
        string \$response = {$serviceName}Resource::class
    ) {
        parent::__construct(\$service, \$request, \$response);
    }
}
";
    }

    private function generateRequestContent($requestName)
    {
        return "<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class {$requestName} extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // Add your validation rules here
        ];
    }
}
";
    }

    private function generateResourceContent($resourceName)
    {
        return "<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class {$resourceName} extends JsonResource
{
    public function toArray(\$request)
    {
        return [
            // Add your resource fields here
        ];
    }
}
";
    }
}
