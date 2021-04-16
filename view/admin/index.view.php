<div class="center-wrapper">
    <table>
        <thead>
            <th>User</th>
            <th>Uid</th>
            <th>Start</th>
            <th>End</th>
            <th>Duration</th>
        </thead>
        <tbody>
            <?php foreach($stamps as $stamp): ?>
            <tr>
                <td><?= isset($stamp->card->user) ? $stamp->card->user->prename . " " . $stamp->card->user->lastname : "NULL"; ?></td>
                <td><?= $stamp->card->uid ?></td>
                <td><?= $stamp->starttime ?></td>
                <td><?= $stamp->endtime ?></td>
                <?php
                    $start = $stamp->starttime;
                    if ($stamp->endtime) {
                        $end = $stamp->endtime;
                    } else {
                        $now =  new DateTime();
                        $end = $now->format("Y-m-d H:i:s");
                    }
                    $diff = strtotime($end) - strtotime($start);
                ?>
                <td><?= $diff ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
