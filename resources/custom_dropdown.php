<?php
function renderCustomDropdown($items, $inputName, $placeholder = "Selecciona un usuario") {
    ?>
    <div class="custom-dropdown">
        <input type="hidden" name="<?= htmlspecialchars($inputName) ?>" id="<?= htmlspecialchars($inputName) ?>">
        <div class="dropdown-selected" onclick="toggleDropdown()"><?= htmlspecialchars($placeholder) ?></div>
        <div class="dropdown-content">
            <?php foreach ($items as $item): ?>
                <div class="dropdown-item" data-value="<?= htmlspecialchars($item['id']) ?>" onclick="selectItem(this)">
                    <p><strong>ID:</strong> <?= htmlspecialchars($item['id']) ?></p>
                    <p><strong>Nombre:</strong> <?= htmlspecialchars($item['nombre'] . ' ' . $item['apellido']) ?></p>
                    <p><strong>Correo:</strong> <?= htmlspecialchars($item['correo']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <script>
        function toggleDropdown() {
            document.querySelector('.dropdown-content').classList.toggle('show');
        }

        function selectItem(element) {
            document.getElementById('<?= $inputName ?>').value = element.getAttribute('data-value');
            document.querySelector('.dropdown-selected').innerText = element.innerText;
            document.querySelector('.dropdown-content').classList.remove('show');
        }

        window.onclick = function(event) {
            if (!event.target.matches('.dropdown-selected')) {
                var dropdowns = document.getElementsByClassName("dropdown-content");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }
    </script>
    <?php
}
?>
