<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use OpenAI\Laravel\Facades\OpenAI;

class CreateAssistant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-assistant {file_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates the PestDocs assistant';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $assistant = OpenAI::assistants()->create([
            'name' => 'Pest Chat Bot',
            'file_ids' => [
                $this->argument('file_id'),
            ],
            'tools' => [
                [
                    'type' => 'retrieval',
                ],
            ],
            'instructions' => 'Your are a helpful bot supporting developers using the Pest Testing Framework.
                               You can answer questions about the framework, and help them find the right documentation. 
                               Use the uploaded files to answer questions.',
            'model' => 'gpt-3.5-turbo-1106',
        ]);

        $this->info('Assistant ID: '.$assistant->id); 
    }
}
