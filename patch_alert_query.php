<?php
$content = file_get_contents('src/app/Jobs/ProcessWaterLevelEvent.php');

$search = <<<EOT
            // Fetch the newly inserted alerts to send emails.
            // Matching them by station_id and triggered_at.
            // Need to handle potential multiple alerts per station in the same batch.
            \$alertQuery = Alert::query();
            foreach (\$alertsInfo as \$info) {
                \$alertQuery->orWhere(function (\$query) use (\$info) {
                    \$query->where('station_id', \$info['station']->id)
                          ->where('triggered_at', \$info['triggered_at'])
                          ->where('level', \$info['level']);
                });
            }
            \$insertedAlerts = \$alertQuery->where('notified', false)->get();
EOT;

$replace = <<<EOT
            // Fetch the newly inserted alerts to send emails.
            // Matching them by station_id and triggered_at.
            // Need to handle potential multiple alerts per station in the same batch.
            \$insertedAlerts = Alert::where('notified', false)->where(function (\$query) use (\$alertsInfo) {
                foreach (\$alertsInfo as \$info) {
                    \$query->orWhere(function (\$subQuery) use (\$info) {
                        \$subQuery->where('station_id', \$info['station']->id)
                                 ->where('triggered_at', \$info['triggered_at'])
                                 ->where('level', \$info['level']);
                    });
                }
            })->get();
EOT;

$content = str_replace($search, $replace, $content);
file_put_contents('src/app/Jobs/ProcessWaterLevelEvent.php', $content);
