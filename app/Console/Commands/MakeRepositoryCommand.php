<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MakeRepositoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:repository {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a repository';

    protected $type = 'class';

    public function handle()
    {
        $fileName = $this->argument('name');
        if (!file_exists("app/Repositories/")) {
            mkdir("app/Repositories/$fileName.php", 0777);
        }
        $repositoryFile = fopen("app/Repositories/" . $fileName . ".php", "w") or die("Unable to open file!");
        $repository = "<?php";
        $repository .= "\n";
        $repository .= "namespace App\Repositories;";
        $repository .= "\n\n";
        $repository .= "class " . $fileName . " {";
        $repository .= "\n\t public function __construct() {";
        $repository .= "\n\n\t}";
        $repository .= "\n}";
        fwrite($repositoryFile, $repository);
        fclose($repositoryFile);

        $this->line("<fg=green>" . $fileName . " successfully created</>");
    }
}
