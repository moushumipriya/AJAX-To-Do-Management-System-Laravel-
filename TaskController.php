
---

## 2️⃣ TaskController.php
```php
<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index() {
        return view('tasks.index');
    }

    public function list() {
        $tasks = Task::where('user_id', Auth::id())->latest()->get();
        return response()->json($tasks);
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'title' => ['required','min:3'],
            'description' => ['nullable','max:255'],
            'status' => ['nullable', Rule::in(['Pending','Completed'])],
        ]);

        $task = Task::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? 'Pending',
            'user_id' => Auth::id(),
        ]);

        return response()->json(['message'=>'Task created','task'=>$task],201);
    }

    public function update(Request $request, Task $task) {
        abort_if($task->user_id !== Auth::id(), 403);

        $validated = $request->validate([
            'title' => ['required','min:3'],
            'description' => ['nullable','max:255'],
            'status' => ['required', Rule::in(['Pending','Completed'])],
        ]);

        $task->update($validated);

        return response()->json(['message'=>'Task updated','task'=>$task]);
    }

    public function destroy(Task $task) {
        abort_if($task->user_id !== Auth::id(), 403);
        $task->delete();
        return response()->json(['message'=>'Task deleted']);
    }
}
