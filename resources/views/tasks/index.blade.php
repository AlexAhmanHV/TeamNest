<x-app-layout>
    <x-slot name="header"><p class="tn-page-title">Tasks: {{ $project->name }}</p></x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex gap-3 text-sm">
                <a href="{{ route('projects.show', $project) }}" class="tn-link">Project</a>
                <a href="{{ route('tasks.trash', $project) }}" class="tn-link">Task Trash</a>
            </div>

            <div class="tn-card">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-3">Create Task</h3>
                <form method="POST" action="{{ route('tasks.store', $project) }}" class="grid md:grid-cols-2 gap-3">@csrf
                    <x-text-input name="title" placeholder="Title" required />
                    <select name="priority" class="tn-input"><option>low</option><option selected>med</option><option>high</option></select>
                    <select name="status" class="tn-input"><option selected>todo</option><option>doing</option><option>done</option></select>
                    <input type="date" name="due_date" class="tn-input" />
                    <select name="assigned_to_user_id" class="tn-input"><option value="">Unassigned</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->name }}</option>@endforeach</select>
                    <textarea name="description" class="tn-input md:col-span-2" placeholder="Description"></textarea>
                    <x-primary-button>Create Task</x-primary-button>
                </form>
            </div>

            <div class="tn-card">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-4">Kanban Board</h3>
                <div
                    x-data="kanbanBoard('{{ csrf_token() }}', '{{ url('/tasks/__TASK__/move') }}')"
                    class="grid md:grid-cols-3 gap-4"
                >
                    @foreach (['todo' => 'To Do', 'doing' => 'Doing', 'done' => 'Done'] as $columnKey => $columnLabel)
                        <section
                            class="rounded-xl border p-3 min-h-[340px]"
                            :class="dragOverStatus === '{{ $columnKey }}' ? 'border-brand-500 bg-brand-500/10' : 'border-slate-800 bg-slate-900/40'"
                            @dragover.prevent="dragOverStatus = '{{ $columnKey }}'"
                            @dragleave="dragOverStatus = null"
                            @drop.prevent="dropTo('{{ $columnKey }}')"
                        >
                            <div class="mb-3 flex items-center justify-between">
                                <h4 class="text-sm font-semibold uppercase tracking-wider text-slate-400">{{ $columnLabel }}</h4>
                                <span class="tn-badge-neutral" x-ref="count-{{ $columnKey }}">{{ ($kanbanTasksByStatus[$columnKey] ?? collect())->count() }}</span>
                            </div>

                            <div class="space-y-2" x-ref="column-{{ $columnKey }}">
                                @forelse (($kanbanTasksByStatus[$columnKey] ?? collect()) as $task)
                                    <article
                                        id="kanban-task-{{ $task->id }}"
                                        draggable="true"
                                        @dragstart="startDrag({ id: {{ $task->id }}, status: '{{ $task->status->value }}' })"
                                        class="cursor-grab active:cursor-grabbing rounded-lg border border-slate-800 bg-slate-900 p-3 shadow-sm hover:border-slate-700"
                                    >
                                        <div class="font-medium text-sm text-white">{{ $task->title }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ strtoupper($task->priority->value) }} @if($task->due_date) • {{ $task->due_date->toDateString() }} @endif</div>
                                        <div class="mt-2 text-xs text-slate-400">{{ $task->assignee?->name ?? 'Unassigned' }}</div>
                                    </article>
                                @empty
                                    <p class="text-xs text-slate-600">No tasks.</p>
                                @endforelse
                            </div>
                        </section>
                    @endforeach
                </div>
            </div>

            <div class="tn-card">
                <div class="flex flex-wrap gap-2 mb-4">
                    @forelse($savedViews as $savedView)
                        <a href="{{ route('tasks.index', $project).'?'.http_build_query($savedView->filters ?? []) }}" class="tn-badge-neutral hover:bg-slate-700">{{ $savedView->name }}</a>
                        <form method="POST" action="{{ route('taskViews.destroy', $savedView) }}">@csrf @method('DELETE')<button class="text-xs text-rose-400">x</button></form>
                    @empty
                        <span class="text-xs text-slate-500">No saved views yet.</span>
                    @endforelse
                </div>

                <form method="POST" action="{{ route('taskViews.store', $project) }}" class="grid md:grid-cols-7 gap-2 mb-4">
                    @csrf
                    <input name="name" class="tn-input" placeholder="Save current filters as..." required>
                    <input type="hidden" name="q" value="{{ request('q') }}">
                    <input type="hidden" name="status" value="{{ request('status') }}">
                    <input type="hidden" name="priority" value="{{ request('priority') }}">
                    <input type="hidden" name="assignee" value="{{ request('assignee') }}">
                    <input type="hidden" name="overdue" value="{{ request('overdue') }}">
                    <button class="tn-btn-secondary md:col-span-2">Save View</button>
                </form>

                <form method="GET" class="grid md:grid-cols-6 gap-2 mb-4">
                    <input name="q" value="{{ request('q') }}" class="tn-input" placeholder="Search" />
                    <select name="status" class="tn-input"><option value="">Any status</option><option value="todo" @selected(request('status')==='todo')>todo</option><option value="doing" @selected(request('status')==='doing')>doing</option><option value="done" @selected(request('status')==='done')>done</option></select>
                    <select name="priority" class="tn-input"><option value="">Any priority</option><option value="low" @selected(request('priority')==='low')>low</option><option value="med" @selected(request('priority')==='med')>med</option><option value="high" @selected(request('priority')==='high')>high</option></select>
                    <select name="assignee" class="tn-input"><option value="">Any assignee</option><option value="me" @selected(request('assignee')==='me')>me</option><option value="unassigned" @selected(request('assignee')==='unassigned')>unassigned</option>@foreach($members as $member)<option value="{{ $member->id }}" @selected(request('assignee')==(string)$member->id)>{{ $member->name }}</option>@endforeach</select>
                    <select name="overdue" class="tn-input"><option value="">Not overdue filter</option><option value="1" @selected(request('overdue')==='1')>overdue only</option></select>
                    <button class="tn-btn-secondary">Apply</button>
                </form>

                <div class="mb-3 flex flex-wrap gap-2 items-center">
                    <select id="bulk-action" class="tn-input text-sm" required>
                        <option value="">Bulk action</option>
                        <option value="status">Set status</option>
                        <option value="priority">Set priority</option>
                        <option value="assign">Set assignee</option>
                        <option value="delete">Delete</option>
                    </select>
                    <select id="bulk-status" class="tn-input text-sm"><option value="">status...</option><option>todo</option><option>doing</option><option>done</option></select>
                    <select id="bulk-priority" class="tn-input text-sm"><option value="">priority...</option><option>low</option><option>med</option><option>high</option></select>
                    <select id="bulk-assignee" class="tn-input text-sm"><option value="">assignee...</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->name }}</option>@endforeach</select>
                    <button type="button" onclick="submitBulkAction()" class="tn-btn-secondary text-sm">Apply to selected</button>
                </div>

                <table class="tn-table"><thead><tr><th><input type="checkbox" onclick="document.querySelectorAll('.bulk-task').forEach(cb => cb.checked = this.checked)"></th><th>Title</th><th>Status</th><th>Priority</th><th>Due</th><th>Assignee</th><th>Actions</th></tr></thead><tbody>
                    @foreach($tasks as $task)
                    <tr class="align-top"><td><input type="checkbox" class="bulk-task" name="task_ids[]" value="{{ $task->id }}"></td><td><a class="tn-link" href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a></td><td><span class="tn-badge-neutral">{{ $task->status->value }}</span></td><td>{{ $task->priority->value }}</td><td>{{ $task->due_date?->toDateString() }}</td><td>{{ $task->assignee?->name ?? 'Unassigned' }}</td>
                        <td class="space-y-2">
                            <form method="POST" action="{{ route('tasks.assign', $task) }}">@csrf
                                <select name="assigned_to_user_id" class="tn-input text-xs py-1" onchange="this.form.submit()"><option value="">Unassigned</option>@foreach($members as $member)<option value="{{ $member->id }}" @selected($task->assigned_to_user_id===$member->id)>{{ $member->name }}</option>@endforeach</select>
                            </form>
                            <div class="flex gap-2 text-xs">
                                <form method="POST" action="{{ route('tasks.complete', $task) }}">@csrf <button class="text-emerald-400 hover:text-emerald-300">Done</button></form>
                                <form method="POST" action="{{ route('tasks.destroy', $task) }}">@csrf @method('DELETE') <button class="text-rose-400 hover:text-rose-300">Delete</button></form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody></table>
                <form id="bulk-action-form" method="POST" action="{{ route('tasks.bulk', $project) }}" class="hidden">@csrf
                    <input type="hidden" name="action" id="bulk-action-field">
                    <input type="hidden" name="status" id="bulk-status-field">
                    <input type="hidden" name="priority" id="bulk-priority-field">
                    <input type="hidden" name="assigned_to_user_id" id="bulk-assignee-field">
                </form>
                <div class="mt-4">{{ $tasks->links() }}</div>
            </div>
        </div>
    </div>

    <script>
        function kanbanBoard(csrfToken, moveRouteTemplate) {
            return {
                draggingTask: null,
                dragOverStatus: null,
                movingTaskId: null,
                startDrag(task) {
                    this.draggingTask = task;
                },
                updateCount(status, delta) {
                    const countNode = this.$refs[`count-${status}`];
                    if (!countNode) {
                        return;
                    }

                    const current = Number.parseInt(countNode.textContent || '0', 10) || 0;
                    countNode.textContent = String(Math.max(0, current + delta));
                },
                async dropTo(status) {
                    if (!this.draggingTask || this.draggingTask.status === status || this.movingTaskId !== null) {
                        this.dragOverStatus = null;
                        return;
                    }

                    const fromStatus = this.draggingTask.status;
                    const taskId = this.draggingTask.id;
                    const taskNode = document.getElementById(`kanban-task-${taskId}`);
                    const targetColumn = this.$refs[`column-${status}`];

                    if (!taskNode || !targetColumn) {
                        this.dragOverStatus = null;
                        return;
                    }

                    this.movingTaskId = taskId;
                    const previousParent = taskNode.parentElement;
                    const previousNextSibling = taskNode.nextElementSibling;
                    targetColumn.prepend(taskNode);
                    this.updateCount(fromStatus, -1);
                    this.updateCount(status, 1);
                    this.draggingTask.status = status;

                    const route = moveRouteTemplate.replace('__TASK__', taskId);
                    const formData = new URLSearchParams();
                    formData.append('_token', csrfToken);
                    formData.append('status', status);

                    try {
                        const response = await fetch(route, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                            },
                            body: formData.toString(),
                        });

                        if (!response.ok) {
                            throw new Error('Failed to move task');
                        }
                    } catch (error) {
                        if (previousParent) {
                            if (previousNextSibling) {
                                previousParent.insertBefore(taskNode, previousNextSibling);
                            } else {
                                previousParent.appendChild(taskNode);
                            }
                        }

                        this.updateCount(status, -1);
                        this.updateCount(fromStatus, 1);
                        this.draggingTask.status = fromStatus;
                    } finally {
                        this.dragOverStatus = null;
                        this.movingTaskId = null;
                    }
                },
            };
        }

        function submitBulkAction() {
            const action = document.getElementById('bulk-action').value;
            if (!action) {
                return;
            }

            const form = document.getElementById('bulk-action-form');
            document.getElementById('bulk-action-field').value = action;
            document.getElementById('bulk-status-field').value = document.getElementById('bulk-status').value;
            document.getElementById('bulk-priority-field').value = document.getElementById('bulk-priority').value;
            document.getElementById('bulk-assignee-field').value = document.getElementById('bulk-assignee').value;

            form.querySelectorAll('input[name=\"task_ids[]\"]').forEach((node) => node.remove());
            document.querySelectorAll('.bulk-task:checked').forEach((checkbox) => {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'task_ids[]';
                hidden.value = checkbox.value;
                form.appendChild(hidden);
            });

            form.submit();
        }
    </script>
</x-app-layout>
