<!--begin::Sidebar-->
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <!--begin::Sidebar Brand-->
    <div class="sidebar-brand">
        <!--begin::Brand Link-->
        <a href="../" class="brand-link">
            <!--begin::Brand Image-->
            <img
                src="assets/img/logop.png"
                alt="Panitia Bona Taon PTS"
                class="brand-image opacity-75 shadow" />
            <!--end::Brand Image-->
            <!--begin::Brand Text-->
            <span class="brand-text fw-light">Panitia Bona Taon PTS</span>
            <!--end::Brand Text-->
        </a>
        <!--end::Brand Link-->
    </div>
    <!--end::Sidebar Brand-->
    <!--begin::Sidebar Wrapper-->
    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <!--begin::Sidebar Menu-->
            <ul
                class="nav sidebar-menu flex-column"
                data-lte-toggle="treeview"
                role="menu"
                data-accordion="false">
                <li class="nav-item ">
                    <a href="./index.php" class="nav-link  <?php if ($menu === 'index') echo 'active'; ?>">
                        <i class="nav-icon bi bi-speedometer"></i>
                        <p>
                            Dashboard
                            <!-- <i class="nav-arrow bi bi-chevron-right"></i> -->
                        </p>
                    </a>
                    <!-- <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="./index.html" class="nav-link">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Dashboard v1</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="./index2.html" class="nav-link ">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Dashboard v2</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="./index3.html" class="nav-link">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Dashboard v3</p>
                            </a>
                        </li>
                    </ul> -->
                </li>
                <?php if ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 2) { ?>
                    <li class="nav-item has-treeview  <?php if ($menu === 'toktok' || $menu === 'toktok-proses') echo 'menu-open'; ?>">
                        <a href="#" class="nav-link <?php if ($menu === 'toktok' || $menu === 'toktok-proses') echo 'active'; ?>">
                            <i class="nav-icon bi bi-cash-coin"></i>
                            <p>
                                Tok-tok Ripe
                                <i class="nav-arrow bi bi-chevron-right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="toktok.php" class="nav-link <?php if ($menu === 'toktok') echo 'active'; ?>">
                                    <i class="bi bi-circle"></i>
                                    <p>Data Tok-tok Ripe</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="toktok-proses.php" class="nav-link <?php if ($menu === 'toktok-proses') echo 'active'; ?>">
                                    <i class="bi bi-circle"></i>
                                    <p>Proses Pembayaran</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php } ?>
                <?php if ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 2 || $_SESSION['role_id'] == 4) { ?>
                    <li class="nav-item has-treeview  <?php if ($menu === 'baju' || $menu === 'baju-proses') echo 'menu-open'; ?>">
                        <a href="#" class="nav-link <?php if ($menu === 'baju' || $menu === 'baju-proses') echo 'active'; ?>">
                            <i class="nav-icon bi bi-person-badge"></i>
                            <p>
                                Baju PTS
                                <i class="nav-arrow bi bi-chevron-right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="baju.php" class="nav-link <?php if ($menu === 'baju') echo 'active'; ?>">
                                    <i class="bi bi-circle"></i>
                                    <p>Pesanan Baju PTS</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="baju-proses.php" class="nav-link <?php if ($menu === 'baju-proses') echo 'active'; ?>">
                                    <i class="bi bi-circle"></i>
                                    <p>Proses Pembayaran</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php } ?>

                <?php if ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 2 || $_SESSION['role_id'] == 4) { ?>
                    <li class="nav-item has-treeview  <?php if ($menu === 'kupon' || $menu === 'kupon-proses') echo 'menu-open'; ?>">
                        <a href="#" class="nav-link <?php if ($menu === 'kupon' || $menu === 'kupon-proses') echo 'active'; ?>">
                            <i class="nav-icon bi bi-cash-coin"></i>
                            <p>
                                Kupon Bajar PTS
                                <i class="nav-arrow bi bi-chevron-right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="kupon.php" class="nav-link <?php if ($menu === 'kupon') echo 'active'; ?>">
                                    <i class="bi bi-circle"></i>
                                    <p>Daftar Kupon</p>
                                </a>
                            </li>
                            <?php if ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 2) { ?>

                                <li class="nav-item">
                                    <a href="kupon-proses.php" class="nav-link <?php if ($menu === 'kupon-proses') echo 'active'; ?>">
                                        <i class="bi bi-circle"></i>
                                        <p>Proses Pembayaran</p>
                                    </a>
                                </li>

                            <?php } ?>
                        </ul>
                    </li>
                <?php } ?>
                <?php if ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 2) { ?>
                    <li class="nav-item">
                        <a href="sumbangan.php" class="nav-link">
                            <i class="nav-icon bi bi-gift-fill"></i>
                            <p>Sumbangan & Tor-tor</p>
                        </a>
                    </li>
                <?php } ?>

                <!-- <?php if ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 2) { ?>
                    <li class="nav-item">
                        <a href="tortor.php" class="nav-link">
                            <i class="nav-icon bi bi-gift-fill"></i>
                            <p>Tor-Tor</p>
                        </a>
                    </li>
                <?php } ?> -->
                <?php if ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 2) { ?>
                    <li class="nav-item has-treeview  <?php if ($menu === 'silua' || $menu === 'silua-proses') echo 'menu-open'; ?>">
                        <a href="#" class="nav-link <?php if ($menu === 'silua' || $menu === 'silua-proses') echo 'active'; ?>">
                            <i class="nav-icon bi bi-cash-coin"></i>
                            <p>
                                Silua
                                <i class="nav-arrow bi bi-chevron-right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="silua.php" class="nav-link <?php if ($menu === 'silua') echo 'active'; ?>">
                                    <i class="bi bi-circle"></i>
                                    <p>Data Silua</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="silua-proses.php" class="nav-link <?php if ($menu === 'silua-proses') echo 'active'; ?>">
                                    <i class="bi bi-circle"></i>
                                    <p>Proses Pembayaran</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php } ?>
                <li class="nav-item has-treeview  <?php if ($menu === 'keluar' || $menu === 'keluar-proses') echo 'menu-open'; ?>">
                    <a href="#" class="nav-link <?php if ($menu === 'keluar' || $menu === 'keluar-proses') echo 'active'; ?>">
                        <i class="nav-icon bi bi-cash-coin"></i>
                        <p>
                            Pengeluaran
                            <i class="nav-arrow bi bi-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">

                        <!-- <?php if ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 2) { ?>
                            <li class="nav-item">
                                <a href="keluar.php" class="nav-link <?php if ($menu === 'keluar') echo 'active'; ?>">
                                    <i class="bi bi-circle"></i>
                                    <p>Data Pengeluaran</p>
                                </a>
                            </li>

                        <?php } ?> -->
                        <li class="nav-item">
                            <a href="keluar-proses.php" class="nav-link <?php if ($menu === 'keluar-proses') echo 'active'; ?>">
                                <i class="bi bi-circle"></i>
                                <p>Proses Pengeluaran</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item has-treeview  <?php if (in_array($menu, ['lappemasukan', 'laptoktok', 'lapripe', 'lapsumbangan', 'lapkaos', 'lapkupon', 'sumbangan-produk', 'lappengeluaran', 'laptor', 'lapsilua'])) echo 'menu-open'; ?>">
                    <a href="#" class="nav-link <?php if (in_array($menu, ['lappemasukan', 'laptoktok', 'lapripe', 'lapsumbangan', 'lapkaos', 'lapkupon', 'sumbangan-produk', 'lappengeluaran', 'laptor', 'lapsilua'])) echo 'active'; ?>">
                        <i class="nav-icon bi bi-file-earmark-text"></i>
                        <p>
                            Laporan
                            <i class="nav-arrow bi bi-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">

                        <!-- Laporan Pemasukan -->
                        <li class="nav-item has-treeview <?php if (in_array($menu, ['lappemasukan', 'laptoktok', 'lapripe', 'lapsumbangan', 'lapkaos', 'lapkupon', 'laptor', 'lapsilua'])) echo 'menu-open'; ?>">
                            <a href="#" class="nav-link <?php if (in_array($menu, ['lappemasukan', 'laptoktok', 'lapripe', 'lapsumbangan', 'lapkaos', 'lapkupon', 'laptor', 'lapsilua'])) echo 'active'; ?>">
                                <i class="bi bi-graph-up"></i>
                                <p>
                                    Laporan Pemasukan
                                    <i class="right bi bi-chevron-right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview ps-3">
                                <li class="nav-item">
                                    <a href="laporan-toktok.php" class="nav-link <?php if ($menu === 'laptoktok') echo 'active'; ?>">
                                        <i class="bi bi-circle"></i>
                                        <p>Tok-tok Ripe</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="laporan-baju.php" class="nav-link <?php if ($menu === 'lapkaos') echo 'active'; ?>">
                                        <i class="bi bi-circle"></i>
                                        <p>Baju PTS</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="laporan_kupon.php" class="nav-link <?php if ($menu === 'lapkupon') echo 'active'; ?>">
                                        <i class="bi bi-circle"></i>
                                        <p>Kupon bajar PTS</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="laporan-sumbangan-dana.php" class="nav-link <?php if ($menu === 'lapsumbangan') echo 'active'; ?>">
                                        <i class="bi bi-circle"></i>
                                        <p>Sumbangan Dana (proposal)</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="laporan-tor-tor.php" class="nav-link <?php if ($menu === 'laptor') echo 'active'; ?>">
                                        <i class="bi bi-circle"></i>
                                        <p>Tor-tor</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="laporan-silua.php" class="nav-link <?php if ($menu === 'lapsilua') echo 'active'; ?>">
                                        <i class="bi bi-circle"></i>
                                        <p>Silua</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="laporan-pemasukan.php" class="nav-link <?php if ($menu === 'lappemasukan') echo 'active'; ?>">
                                        <i class="bi bi-circle"></i>
                                        <p>Semua Pemasukan</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Laporan Pengeluaran -->
                        <li class="nav-item has-treeview">
                            <a href="#" class="nav-link <?php if (in_array($menu, ['lapacara', 'lapkonsumsi', 'lapperlengkapan', 'lappengeluaran'])) echo 'active'; ?>">
                                <i class="bi bi-wallet2"></i>
                                <p>
                                    Laporan Pengeluaran
                                    <i class="right bi bi-chevron-right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview ps-3">
                                <li class="nav-item">
                                    <a href="#.php" class="nav-link <?php if ($menu === 'lapacara') echo 'active'; ?>">
                                        <i class="bi bi-circle"></i>
                                        <p>Seksi Acara</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#.php" class="nav-link <?php if ($menu === 'lapkonsumsi') echo 'active'; ?>">
                                        <i class="bi bi-circle"></i>
                                        <p>Seksi Konsumsi</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#.php" class="nav-link <?php if ($menu === 'lapperlengkapan') echo 'active'; ?>">
                                        <i class="bi bi-circle"></i>
                                        <p>Seksi Perlengkapan</p>
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a href="#" class="nav-link <?php if ($menu === 'lappengeluaran') echo 'active'; ?>">
                                        <i class="bi bi-circle"></i>
                                        <p>Semua Pengeluaran</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Laporan Sumbangan (bisa tetap terpisah jika perlu) -->
                        <li class="nav-item">
                            <a href="laporan-sumbangan-produk.php" class="nav-link <?php if ($menu === 'sumbangan-produk') echo 'active'; ?>">
                                <i class="bi bi-circle"></i>
                                <p>Sumbangan Produk</p>
                            </a>
                        </li>

                    </ul>

                </li>
                <?php if ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 2) { ?>
                    <li class="nav-item">
                        <a href="anggota.php" class="nav-link">
                            <i class="nav-icon bi bi-person-fill"></i>
                            <p>Anggota</p>
                        </a>
                    </li>

                <?php } ?>

            </ul>
            <!--end::Sidebar Menu-->
        </nav>
    </div>
    <!--end::Sidebar Wrapper-->
</aside>
<!--end::Sidebar-->