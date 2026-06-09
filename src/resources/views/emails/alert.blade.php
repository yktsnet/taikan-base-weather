<!DOCTYPE html>
<html>
<head>
    <title>水位アラート通知</title>
</head>
<body>
    <h2>水位アラート通知</h2>
    <p>以下の観測所で水位が閾値を超えました。</p>
    <ul>
        <li><strong>対象観測所:</strong> {{ $station->name }}</li>
        <li><strong>河川名:</strong> {{ $station->river_name }}</li>
        <li><strong>都道府県:</strong> {{ $station->prefecture }}</li>
        <li><strong>観測時刻:</strong> {{ $alert->triggered_at }}</li>
        <li><strong>水位:</strong> {{ $alert->level_m }} m</li>
        <li><strong>警戒レベル:</strong> {{ strtoupper($alert->level) }}</li>
    </ul>
    <p>※本メールは自動送信されています。</p>
</body>
</html>
