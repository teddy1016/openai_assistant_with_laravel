<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use ErlandMuchasaj\LaravelFileUploader\FileUploader;
use Illuminate\Support\Facades\Storage;
//use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Threads\Runs\ThreadRunResponse;
use Session;
use OpenAI;

class AssistantController extends Controller
{
    public function index(Request $request) {
        $answer = "";
        return view('assistant')->with('answer', $answer);
    }

    public function upload(Request $request) {
        $client = OpenAI::client(env('OPENAI_API_KEY'));
        
        $max_size = (int) ini_get('upload_max_filesize') * 1000;
    
        $extensions = implode(',', FileUploader::allExtensions());
        
        $request->validate([
            'file' => [
                'required',
                'file',
                'file',
                'mimes:' . $extensions,
                'max:'.$max_size,
            ]
        ]);
    
        $file = $request->file('file');
    
        $response = FileUploader::store($file);
        $storage_url = $response["path"];
        ///upload file to openai///
        $uploadedFile = $client->files()->upload([
            'file' => Storage::disk('local')->readStream($storage_url),
            'purpose' => 'assistants',
        ]);
        
        $file_id = $uploadedFile->id;
        ///create assistant///
        $assistant = $client->assistants()->create([
            'name' => 'Assistant Bot',
            'file_ids' => [$file_id],
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

        Session::put('assist_id', $assistant->id);

        return redirect()
                ->back()
                ->with('success',$assistant->id)
                ->with('file', $response);
    }

    public function send(Request $request) {
        $question = $request->question;
        $threadRun = $this->createAndRunThread($question);
        $answer = $this->loadAnswer($threadRun);
        return view('assistant')->with('answer', $answer);
    }

    private function createAndRunThread($question): ThreadRunResponse
    {
        $client = OpenAI::client(env('OPENAI_API_KEY'));
        $assist_id = Session::get('assist_id');
        return $client->threads()->createAndRun([
            'assistant_id' => $assist_id,
            'thread' => [
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $question,
                    ],
                ],
            ],
        ]);
    }

    private function loadAnswer(ThreadRunResponse $threadRun)
    {
        $client = OpenAI::client(env('OPENAI_API_KEY'));
        while(in_array($threadRun->status, ['queued', 'in_progress'])) {
            $threadRun = $client->threads()->runs()->retrieve(
                threadId: $threadRun->threadId,
                runId: $threadRun->id,
            );
        }

        if ($threadRun->status !== 'completed') {
            $this->error = 'Request failed, please try again';
        }

        $messageList = $client->threads()->messages()->list(
            threadId: $threadRun->threadId,
        );
        return $messageList->data[0]->content[0]->text->value;   
    }
}