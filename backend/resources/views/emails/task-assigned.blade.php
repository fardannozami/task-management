<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Assigned Notification</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .task-details { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .footer { margin-top: 30px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Task Assigned Notification</h1>
        </div>

        <p>Hello {{ $assignedUserName }},</p>

        <p>You have been assigned a new task. Here are the details:</p>

        <div class="task-details">
            <h3>{{ $taskTitle }}</h3>
            <p><strong>Status:</strong> {{ ucfirst($taskStatus) }}</p>
            <p><strong>Priority:</strong> {{ ucfirst($taskPriority) }}</p>
            <p><strong>Due Date:</strong> {{ $dueDate ? $dueDate->format('Y-m-d H:i') : 'No due date' }}</p>
            <p><strong>Assigned By:</strong> {{ $assignedByName }}</p>
        </div>

        @if($taskDescription)
        <div class="task-details">
            <h4>Description</h4>
            <p>{{ $taskDescription }}</p>
        </div>
        @endif

        <p>Please log in to the task management system to view and update the task status.</p>

        <div class="footer">
            <p>This is an automated notification. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
