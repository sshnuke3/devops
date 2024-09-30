<?php
// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 文件路径
$todoFilePath = 'tasks/todo_list.json';
$doneFilePath = 'tasks/done_list.json';

// 检查并创建任务目录
if (!is_dir('tasks')) {
    mkdir('tasks', 0777, true);
}

// 处理表单提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $task = [
        'name' => $_POST['name'],
        'repoUrl' => $_POST['repoUrl'],
        'branches' => $_POST['branches'],
        'releaseMode' => $_POST['releaseMode']
    ];

    // 读取现有任务列表或初始化为空数组
    $tasks = file_exists($todoFilePath) ? json_decode(file_get_contents($todoFilePath), true) : [];

    // 将新任务添加到数组中
    array_push($tasks, $task);

    // 将更新后的任务列表写回文件
    file_put_contents($todoFilePath, json_encode($tasks, JSON_PRETTY_PRINT));

    // 提交成功后重定向以避免重复提交
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// 处理标记为完成的任务
if ($_GET && isset($_GET['id'])) {
    $taskId = $_GET['id'];

    $tasks = file_exists($todoFilePath) ? json_decode(file_get_contents($todoFilePath), true) : [];
    $doneTasks = file_exists($doneFilePath) ? json_decode(file_get_contents($doneFilePath), true) : [];

    // 查找并删除todo list中的任务
    foreach ($tasks as $key => $task) {
        if ($task['name'] === $taskId) {
            // 添加完成时间
            $task['completedAt'] = date('Y-m-d H:i:s');
            $doneTasks[] = $task;
            unset($tasks[$key]);
            break;
        }
    }

    // 重新索引数组
    $tasks = array_values($tasks);

    // 写入文件
    file_put_contents($todoFilePath, json_encode($tasks, JSON_PRETTY_PRINT));
    file_put_contents($doneFilePath, json_encode($doneTasks, JSON_PRETTY_PRINT));

    // 标记完成成功后重定向
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// 主页内容
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Task Manager</title>
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f4f4f4;
    }
    h1, h2 {
        text-align: center;
        margin-top: 20px;
    }
    form {
        width: 60%;
        max-width: 500px;
        margin: 20px auto;
        padding: 20px;
        background-color: #fff;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        border-radius: 5px;
    }
    label {
        display: block;
        margin-bottom: 5px;
    }
    input[type="text"],
    input[type="url"],
    textarea,
    select,
    input[type="submit"] {
        width: 100%;
        padding: 10px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
    }
    input[type="submit"] {
        background-color: #4CAF50;
        color: white;
        cursor: pointer;
    }
    input[type="submit"]:hover {
        background-color: #45a049;
    }
    table {
        width: 60%;
        max-width: 500px;
        margin: 20px auto;
        border-collapse: collapse;
        background-color: #fff;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        border-radius: 5px;
    }
    th, td {
        padding: 15px;
        text-align: left;
        border: 1px solid #ddd;
    }
    th {
        background-color: #4CAF50;
        color: white;
    }
    tr:nth-child(even) {
        background-color: #f2f2f2;
    }
    a {
        color: #4CAF50;
        text-decoration: none;
    }
    a:hover {
        text-decoration: underline;
    }
</style>
</head>
<body>
<h1>线上发布任务管理系统</h1>
<form action="" method="post">
    <label for="name">你的姓名(如果填错的话就没法通知你了):</label>
    <input type="text" id="name" name="name" required>
    <label for="repoUrl">git仓库 URL:</label>
    <input type="url" id="repoUrl" name="repoUrl" required>
    <label for="branches">分支清单一行一个(如果是IP分流把分流比例也写上):</label>
    <textarea id="branches" name="branches" rows="4" cols="50"></textarea>
    <label for="releaseMode">分流方式:</label>
    <select id="releaseMode" name="releaseMode" required>
        <option value="noCanary" selected>不分流</option>
        <option value="packageSplit">不同包体客户端分流</option>
        <option value="ipSplit">同一包体IP分流</option>
    </select>
    <input type="submit" value="Submit">
</form>

<h2 style="text-align: center;">待发布任务清单(如果一直挂在这就说明还没做，急的话记得来催我，这里没做完的不会清)</h2>
<table>
    <thead>
        <tr>
            <th>姓名</th>
            <th>仓库URL</th>
            <th>分支清单</th>
            <th>分流方式</th>
            <th>处理</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if (file_exists($todoFilePath)) {
            $tasks = json_decode(file_get_contents($todoFilePath), true);
            foreach ($tasks as $task) {
                echo "<tr>";
                echo "<td>{$task['name']}</td>";
                echo "<td>{$task['repoUrl']}</td>";
                echo "<td>{$task['branches']}</td>";
                echo "<td>{$task['releaseMode']}</td>";
                echo "<td><a href='?id={$task['name']}'>Done</a></td>";
                echo "</tr>";
            }
        }
        ?>
    </tbody>
</table>

<h2 style="text-align: center;">已发布任务清单(如果忘记通知你就自己来这里看，只显示当天的，每天会自动清)</h2>
<table>
    <thead>
        <tr>
            <th>姓名</th>
            <th>仓库URL</th>
            <th>分支清单</th>
            <th>分流方式</th>
            <th>完成时间</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if (file_exists($doneFilePath)) {
            $tasks = json_decode(file_get_contents($doneFilePath), true);

            // 对已完成任务按完成时间倒序排序
            usort($tasks, function($a, $b) {
                return strtotime($b['completedAt']) - strtotime($a['completedAt']);
            });

            foreach ($tasks as $task) {
                echo "<tr>";
                echo "<td>{$task['name']}</td>";
                echo "<td>{$task['repoUrl']}</td>";
                echo "<td>{$task['branches']}</td>";
                echo "<td>{$task['releaseMode']}</td>";
                echo "<td>{$task['completedAt']}</td>";
                echo "</tr>";
            }
        }
        ?>
    </tbody>
</table>
</body>
</html>
