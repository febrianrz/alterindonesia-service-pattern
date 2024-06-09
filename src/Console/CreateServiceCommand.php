<?php

namespace Alterindonesia\ServicePattern\Console;

use App\Models\Test;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class CreateServiceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:service {serviceName} {--model=}';

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
        $serviceName = $this->argument('serviceName');
        $modelName = $this->option('model');
        if(!$modelName) {
            $this->error("Please fill --model option");
            return ;
        }
        $folder = app_path('Services');
        $filePath = $folder."/".$serviceName.".php";
        //if folder not exists, then create folder

        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        if(file_exists($filePath)){
            $this->error("Filename {$serviceName} already exists");
            return ;
        }


        $content = $this->generateContent($serviceName, $modelName);

        file_put_contents($filePath, $content);

        $this->info("Service {$serviceName} created successfully.");

    }

    private function generateContent($serviceName, $modelName) {
        return "<?php

namespace App\Services;

use Alterindonesia\ServicePattern\ServiceEloquents\BaseServiceEloquent;
use App\Models\\{$modelName};

class {$serviceName} extends BaseServiceEloquent
{
     public function __construct(
        {$modelName} \$model
    ) {
        parent::__construct(\$model);
    }
}
";
    }
}
