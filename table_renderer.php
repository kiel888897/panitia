<?php if (!empty($rows)): ?>
    <table class="table table-bordered table-striped dataTable">
        <thead>
            <tr>
                <?php foreach (array_keys($rows[0]) as $col): ?>
                    <th><?= htmlspecialchars(ucfirst($col)) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <?php foreach ($r as $v): ?>
                        <td><?= htmlspecialchars($v) ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p><em>Tidak ada data.</em></p>
<?php endif; ?>