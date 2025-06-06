<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
redirectIfNotLoggedIn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $nisn = mysqli_real_escape_string($conn, $_POST['nisn']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $kelas = mysqli_real_escape_string($conn, $_POST['kelas']);
    $absen = (int) $_POST['absen'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $tanggal_lahir = mysqli_real_escape_string($conn, $_POST['tanggal_lahir']);

    $foto = '';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $target_dir = __DIR__ . "/../assets/uploads/";
        $target_file = $target_dir . basename($_FILES["foto"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $check = getimagesize($_FILES["foto"]["tmp_name"]);
        if ($check !== false) {
            $foto = uniqid() . '.' . $imageFileType;
            move_uploaded_file($_FILES["foto"]["tmp_name"], $target_dir . $foto);
        }
    }

    if (isset($_POST['add'])) {
        $check_nisn_sql = "SELECT id FROM siswa WHERE nisn = '$nisn'";
        $check_nisn_result = mysqli_query($conn, $check_nisn_sql);
        if (mysqli_num_rows($check_nisn_result) > 0) {
            header("Location: siswa.php?error=nisn_exists");
            exit();
        }

        $sql = "INSERT INTO siswa (nisn, nama, kelas, absen, status, tanggal_lahir, foto)
                VALUES ('$nisn', '$nama', '$kelas', $absen, '$status', '$tanggal_lahir', '$foto')";
        if (mysqli_query($conn, $sql)) {
            header("Location: siswa.php?success=add");
            exit();
        } else {
            header("Location: siswa.php?error=add_failed");
            exit();
        }

    } elseif (isset($_POST['edit'])) {
        $check_nisn_sql = "SELECT id FROM siswa WHERE nisn = '$nisn' AND id != $id";
        $check_nisn_result = mysqli_query($conn, $check_nisn_sql);
        if (mysqli_num_rows($check_nisn_result) > 0) {
            header("Location: siswa.php?error=nisn_exists_other");
            exit();
        }

        $sql = "UPDATE siswa SET
                nisn = '$nisn',
                nama = '$nama',
                kelas = '$kelas',
                absen = $absen,
                status = '$status',
                tanggal_lahir = '$tanggal_lahir'";

        if (!empty($foto)) {
            $sql .= ", foto = '$foto'";
        }

        $sql .= " WHERE id = $id";

        if (mysqli_query($conn, $sql)) {
            header("Location: siswa.php?success=edit");
            exit();
        } else {
            header("Location: siswa.php?error=edit_failed");
            exit();
        }

    } elseif (isset($_POST['delete'])) {
        $id = (int) $_POST['id'];
        $sql = "DELETE FROM siswa WHERE id = $id";
        if (mysqli_query($conn, $sql)) {
            header("Location: siswa.php?success=delete");
            exit();
        } else {
            header("Location: siswa.php?error=delete_failed");
            exit();
        }
    }
    header("Location: siswa.php");
    exit();
}

$siswa = mysqli_query($conn, "SELECT * FROM siswa ORDER BY kelas, absen");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <?php include '../includes/header.php'; ?>
    <title>Manajemen Siswa</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes fadeInUp {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate__fadeInUp {
            animation-name: fadeInUp;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(5px);
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 500px;
            position: relative;
            animation: animatezoom 0.6s;
        }

        @keyframes animatezoom {
            from {
                transform: scale(0)
            }

            to {
                transform: scale(1)
            }
        }

        .close {
            position: absolute;
            top: 15px;
            right: 25px;
            color: #aaa;
            font-size: 30px;
            font-weight: bold;
            transition: 0.3s;
        }

        .close:hover,
        .close:focus {
            color: #333;
            text-decoration: none;
            cursor: pointer;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-badge.lulus {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-badge.tidak-lulus {
            background-color: #fee2e2;
            color: #991b1b;
        }

        @media (max-width: 768px) {

            .table-responsive table,
            .table-responsive thead,
            .table-responsive tbody,
            .table-responsive th,
            .table-responsive td,
            .table-responsive tr {
                display: block;
            }

            .table-responsive thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }

            .table-responsive tr {
                border: 1px solid #ccc;
                margin-bottom: 5px;
            }

            .table-responsive td {
                border: none;
                border-bottom: 1px solid #eee;
                position: relative;
                padding-left: 50%;
                text-align: right;
            }

            .table-responsive td:before {
                position: absolute;
                top: 6px;
                left: 6px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                content: attr(data-label);
                font-weight: bold;
                text-align: left;
            }

            .table-responsive td:last-child {
                border-bottom: 0;
            }
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="fixed top-0 left-0 w-full z-50">
        <?php include 'includes/admin_header.php'; ?>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-8 mt-32 animate__animated animate__fadeInUp">
        <div class="bg-white rounded-xl shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-users text-blue-500 mr-3"></i>
                Manajemen Data Siswa
            </h1>

            <?php if (isset($_GET['success'])): ?>
                <?php
                $message = "";
                $icon = "";
                $color = "";
                if ($_GET['success'] == 'add') {
                    $message = "Data siswa berhasil ditambahkan!";
                    $icon = "fas fa-check-circle";
                    $color = "green";
                } elseif ($_GET['success'] == 'edit') {
                    $message = "Data siswa berhasil diperbarui!";
                    $icon = "fas fa-check-circle";
                    $color = "green";
                } elseif ($_GET['success'] == 'delete') {
                    $message = "Data siswa berhasil dihapus!";
                    $icon = "fas fa-check-circle";
                    $color = "green";
                }
                ?>
                <div
                    class="bg-<?= $color ?>-100 border-l-4 border-<?= $color ?>-500 text-<?= $color ?>-700 p-4 mb-6 rounded">
                    <i class="<?= $icon ?> mr-2"></i>
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <?php
                $message = "";
                $icon = "fas fa-exclamation-triangle";
                $color = "red";
                if ($_GET['error'] == 'nisn_exists') {
                    $message = "Gagal menambah siswa: NISN sudah terdaftar!";
                } elseif ($_GET['error'] == 'nisn_exists_other') {
                    $message = "Gagal memperbarui siswa: NISN sudah terdaftar pada siswa lain!";
                } elseif ($_GET['error'] == 'add_failed') {
                    $message = "Gagal menambahkan data siswa.";
                } elseif ($_GET['error'] == 'edit_failed') {
                    $message = "Gagal memperbarui data siswa.";
                } elseif ($_GET['error'] == 'delete_failed') {
                    $message = "Gagal menghapus data siswa.";
                }
                ?>
                <div
                    class="bg-<?= $color ?>-100 border-l-4 border-<?= $color ?>-500 text-<?= $color ?>-700 p-4 mb-6 rounded">
                    <i class="<?= $icon ?> mr-2"></i>
                    <?= $message ?>
                </div>
            <?php endif; ?>


            <div
                class="flex flex-col md:flex-row justify-between items-center mb-6 space-y-4 md:space-y-0 md:space-x-4">
                <div class="flex space-x-4">
                    <button id="addStudentBtn"
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 transition-all flex items-center">
                        <i class="fas fa-plus mr-2"></i> Tambah Siswa
                    </button>
                    <a href="import.php"
                        class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 focus:ring-4 focus:ring-green-300 transition-all flex items-center">
                        <i class="fas fa-file-import mr-2"></i> Import Data
                    </a>
                </div>
                <a href="dashboard.php"
                    class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-300 transition-all flex items-center">
                    <i class="fas fa-home mr-2"></i> Kembali ke Dashboard
                </a>
            </div>

            <div id="studentModal" class="modal">
                <div class="modal-content animate__animated animate__zoomIn">
                    <span class="close">&times;</span>
                    <h2 id="modalTitle" class="text-2xl font-bold text-gray-800 mb-6">Tambah Siswa Baru</h2>
                    <form method="POST" class="space-y-4" enctype="multipart/form-data">
                        <input type="hidden" id="studentId" name="id">
                        <div>
                            <label for="nisn" class="block text-sm font-medium text-gray-700">NISN</label>
                            <input type="text" id="nisn" name="nisn"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                required>
                        </div>
                        <div>
                            <label for="nama" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                            <input type="text" id="nama" name="nama"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                required>
                        </div>
                        <div>
                            <label for="kelas" class="block text-sm font-medium text-gray-700">Kelas</label>
                            <input type="text" id="kelas" name="kelas"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                required>
                        </div>
                        <div>
                            <label for="absen" class="block text-sm font-medium text-gray-700">No. Absen</label>
                            <input type="number" id="absen" name="absen"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                required>
                        </div>
                        <div>
                            <label for="tanggal_lahir" class="block text-sm font-medium text-gray-700">Tanggal
                                Lahir</label>
                            <input type="date" id="tanggal_lahir" name="tanggal_lahir"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                required>
                        </div>
                        <div>
                            <label for="foto" class="block text-sm font-medium text-gray-700">Foto Profil</label>
                            <input type="file" id="foto" name="foto"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                accept="image/*">
                            <div id="fotoPreviewContainer" class="mt-2 hidden">
                                <span class="block text-sm font-medium text-gray-700 mb-1">Preview:</span>
                                <img id="fotoPreview" src="#" alt="Foto Preview"
                                    class="w-20 h-20 rounded-full object-cover border border-gray-300">
                            </div>
                        </div>
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select id="status" name="status"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                required>
                                <option value="Lulus">Lulus</option>
                                <option value="Tidak Lulus">Tidak Lulus</option>
                            </select>
                        </div>
                        <div class="pt-4">
                            <button type="submit" id="submitBtn" name="add"
                                class="w-full bg-blue-600 text-white px-4 py-2 rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg shadow-md">
                <table class="min-w-full bg-white data-table">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                Foto</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                NISN</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                Nama</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                Kelas</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                Absen</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                Tanggal Lahir</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php while ($row = mysqli_fetch_assoc($siswa)): ?>
                            <tr class="<?= $no % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?>">
                                <td class="px-6 py-4 whitespace-nowrap" data-label="Foto">
                                    <?php if (!empty($row['foto'])): ?>
                                        <img src="/../assets/uploads/<?= htmlspecialchars($row['foto']) ?>"
                                            class="w-10 h-10 rounded-full object-cover"
                                            alt="<?= htmlspecialchars($row['nama']) ?>">
                                    <?php else: ?>
                                        <img src="../assets/images/siswa/default-profile.png"
                                            class="w-10 h-10 rounded-full object-cover" alt="Default">
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" data-label="NISN">
                                    <?= htmlspecialchars($row['nisn']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" data-label="Nama">
                                    <?= htmlspecialchars($row['nama']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" data-label="Kelas">
                                    <?= htmlspecialchars($row['kelas']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" data-label="Absen">
                                    <?= htmlspecialchars($row['absen']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" data-label="Tanggal Lahir">
                                    <?= $row['tanggal_lahir'] ? date('d/m/Y', strtotime($row['tanggal_lahir'])) : '-' ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm" data-label="Status">
                                    <span class="status-badge <?= strtolower(str_replace(' ', '-', $row['status'])) ?>">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" data-label="Aksi">
                                    <button class="text-blue-600 hover:text-blue-900 mr-3"
                                        onclick="editStudent(<?= $row['id'] ?>, '<?= htmlspecialchars($row['nisn'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['nama'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['kelas'], ENT_QUOTES) ?>', <?= $row['absen'] ?>, '<?= htmlspecialchars($row['status'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['foto'], ENT_QUOTES) ?>')">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" style="display:inline;"
                                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <button type="submit" name="delete" class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash-alt"></i> Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php if (mysqli_num_rows($siswa) == 0): ?>
                            <tr>
                                <td colspan="8" class="px-6 py-4 text-center text-gray-500">Tidak ada data siswa.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        var modal = document.getElementById("studentModal");

        var addBtn = document.getElementById("addStudentBtn");

        var span = document.getElementsByClassName("close")[0];

        var modalTitle = document.getElementById("modalTitle");
        var studentId = document.getElementById("studentId");
        var nisnInput = document.getElementById("nisn");
        var namaInput = document.getElementById("nama");
        var kelasInput = document.getElementById("kelas");
        var absenInput = document.getElementById("absen");
        var tanggalLahirInput = document.getElementById("tanggal_lahir");
        var fotoInput = document.getElementById("foto");
        var fotoPreview = document.getElementById("fotoPreview");
        var fotoPreviewContainer = document.getElementById("fotoPreviewContainer");
        var statusSelect = document.getElementById("status");
        var submitBtn = document.getElementById("submitBtn");
        var studentForm = modal.querySelector('form');


        addBtn.onclick = function () {
            modalTitle.textContent = "Tambah Siswa Baru";
            studentId.value = "";
            nisnInput.value = "";
            namaInput.value = "";
            kelasInput.value = "";
            absenInput.value = "";
            tanggalLahirInput.value = "";
            fotoInput.value = "";
            fotoPreview.src = "#";
            fotoPreviewContainer.classList.add('hidden');
            statusSelect.value = "Lulus";
            submitBtn.name = "add";
            submitBtn.textContent = "Simpan";
            studentForm.reset();
            modal.style.display = "block";
        }

        function editStudent(id, nisn, nama, kelas, absen, status, foto) {
            modalTitle.textContent = "Edit Data Siswa";
            studentId.value = id;
            nisnInput.value = nisn;
            namaInput.value = nama;
            kelasInput.value = kelas;
            absenInput.value = absen;
            statusSelect.value = status;
            submitBtn.name = "edit";
            submitBtn.textContent = "Update";

            if (foto && foto !== '') {
                fotoPreview.src = "/../assets/uploads/" + foto;
                fotoPreviewContainer.classList.remove('hidden');
            } else {
                fotoPreview.src = "#";
                fotoPreviewContainer.classList.add('hidden');
            }
            fotoInput.value = "";

            modal.style.display = "block";
        }

        span.onclick = function () {
            modal.style.display = "none";
        }

        window.onclick = function (event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        fotoInput.addEventListener('change', function (event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    fotoPreview.src = e.target.result;
                    fotoPreviewContainer.classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            } else {
                fotoPreview.src = "#";
                fotoPreviewContainer.classList.add('hidden');
            }
        });
    </script>
    <script src="../assets/js/script.js"></script>
</body>

</html>