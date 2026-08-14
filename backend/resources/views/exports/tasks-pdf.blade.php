<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks Export</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { font-size: 18px; margin-bottom: 5px; }
        .header p { color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background-color: #f8f9fa; border: 1px solid #ddd; padding: 8px; text-align: left; font-weight: bold; }
        td { border: 1px solid #ddd; padding: 8px; }
        tr:nth-child(even) { background-color: #f8f9fa; }
        .footer { margin-top: 30px; font-size: 10px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Tasks Export Report</h1>
        <p>Generated on: {{ $exportedAt }}</p>
        <p>Total Tasks: {{ $tasks->count() }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Assigned To</th>
                <th>Created By</th>
                <th>Due Date</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tasks as $task)
                <tr>
                    <td>{{ $task->id }}</td>
                    <td>{{ $task->title }}</td>
                    <td>{{ ucfirst($task->status) }}</td>
                    <td>{{ ucfirst($task->priority) }}</td>
                    <td>{{ $task->assignedUser?->name ?? 'Unassigned' }}</td>
                    <td>{{ $task->creator?->name ?? 'Unknown' }}</td>
                    <td>{{ $task->due_date?->format('Y-m-d H:i') ?? 'No due date' }}</td>
                    <td>{{ $task->created_at?->format('Y-m-d H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>This is an automated export from the Task Management System.</p>
    </div>
</body>
</html>
