<?php
// Start session and include authentication
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}
require_once '../config/auth.php';

// Path detection for assets
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$is_subfolder = ($current_dir !== 'road_and_infra_dept');
$is_lgu_module = ($current_dir === 'lgu_officer_module');
$asset_base = $is_subfolder ? '../user_and_access_management_module/assets/img/' : 'user_and_access_management_module/assets/img/';


// Require login to access this page
$auth->requireLogin();
?>

<nav class="sidebar-nav">
  <div class="sidebar-content">
    <!-- <div class="sidebar-header">
      <img src="assets/img/logocityhall.png" alt="LGU Logo" class="sidebar-logo" />
      <h3>LGU Portal</h3>
      <p class="user-role"><?php echo htmlspecialchars($auth->getUserRole(), ENT_QUOTES, 'UTF-8'); ?></p>
    </div> -->

    <div class="site-logo">
      <img src="<?php echo $asset_base; ?>logocityhall.png" />

      <p class="user-role">LGU Officer</p>

    </div>

    <ul class="sidebar-menu">
        <li>
          <a href="../lgu_officer_module/index.php" class="nav-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-speedometer2" viewBox="0 0 16 16">
              <path d="M8 4a.5.5 0 0 1 .5.5V6a.5.5 0 0 1-1 0V4.5A.5.5 0 0 1 8 4M3.732 5.732a.5.5 0 0 1 .707 0l.915.914a.5.5 0 0 1-.708.708l-.914-.915a.5.5 0 0 1 0-.707M2 10a.5.5 0 0 1 .5-.5h1.586a.5.5 0 0 1 0 1H2.5A.5.5 0 0 1 2 10m9.5 0a.5.5 0 0 1 .5-.5h1.5a.5.5 0 0 1 0 1H12a.5.5 0 0 1-.5-.5m.754-4.246a.389.389 0 0 0-.527-.02L7.547 9.31a.91.91 0 1 0 1.302 1.258l3.434-4.297a.389.389 0 0 0-.029-.518z"/>
              <path fill-rule="evenodd" d="M0 10a8 8 0 1 1 15.547 2.661c-.442 1.253-1.845 1.602-2.932 1.25l-1.173-.378a15 15 0 0 0-1.071-.306l-1.037-.255c-.458-.113-.923-.23-1.396-.35l-.547-.14c-.643-.163-1.293-.331-1.953-.503L5.47 12.112c-.503-.13-.945-.251-1.341-.36a15 15 0 0 0-1.021-.247l-1.096-.217c-1.095-.218-1.513-.733-1.127-1.977A7.97 7.97 0 0 1 0 10m8 6c.074 0 .148-.004.22-.012l.004-.056a5 5 0 0 0-.15-.864L7.404 14.8l-.007.031c-.167.763.585 1.168 1.14 1.168.077 0 .15-.008.216-.017l.005-.058a7 7 0 0 0-.15-.98l-.013-.056c-.164-.717.585-1.134 1.184-1.134.144 0 .283.021.415.059l.044.016a5.8 5.8 0 0 0 .15-.945l.003-.06c.086-1.567-1.887-1.186-1.993-2.503-.072-1.109 1.218-2.215 2.15-2.215.177 0 .35.039.512.116l.044.021c.3.151.446.471.63.777.282.47.614.748 1.033.748q.123 0 .257-.025l.045-.01c.445-.102.639-.309.673-.535l.006-.062c.072-1.353-1.503-1.623-1.503-2.937 0-.327.101-.694.301-1.022l.026-.044c.31-.519.852-.617 1.293-.617q.13 0 .252.017l.043.006c.419.051.737.227.828.592l.007.058c.205 1.543-1.978 1.345-2.122 2.746-.073 1.16 1.109 2.112 2.154 2.112.152 0 .297-.02.435-.058l.041-.01c.433-.12.63-.33.675-.527l.003-.045c.213-1.482-2.094-1.27-2.094-2.729 0-1.173 1.291-2.203 2.323-2.203a3 3 0 0 1 .684.081 33 33 0 0 1 .69 3.038c.19 1.378-.461 2.532-1.325 3.506l-.044.05c-.341.388-.736.718-1.144 1.013l-.014.01c-.407.298-.823.556-1.24.773l-.045.023c-.377.193-.759.354-1.129.485l-.08.028a5 5 0 0 1-1.182.266 8 8 0 0 1-1.585-.051l-.04-.005c-.404-.05-.813-.132-1.217-.245l-.015-.005c-.402-.11-.803-.249-1.198-.415l-.044-.019c-.352-.15-.698-.319-1.03-.505l-.014-.008c-.406-.23-.793-.489-1.155-.776l-.012-.01c-.367-.291-.708-.61-1.019-.958l-.02-.02c-.352-.396-.656-.832-.899-1.301l-.012-.025a6 6 0 0 1-.582-1.554l-.009-.045c-.13-.672-.188-1.348-.172-2.025a6 6 0 0 1 .132-1.198z"/>
            </svg>Dashboard</a>
        </li>

        <li>
          <a href="../lgu_officer_module/road_reporting_overview.php" class="nav-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-flag"
              viewBox="0 0 16 16">
              <path
                d="M14.778.085A.5.5 0 0 1 15 .5V8a.5.5 0 0 1-.314.464L14.5 8l.186.464-.003.001-.006.003-.023.009a12 12 0 0 1-.397.15c-.264.095-.631.223-1.047.35-.816.252-1.879.523-2.71.523-.847 0-1.548-.28-2.158-.525l-.028-.01C7.68 8.71 7.14 8.5 6.5 8.5c-.7 0-1.638.23-2.437.477A20 20 0 0 0 3 9.342V15.5a.5.5 0 0 1-1 0V.5a.5.5 0 0 1 1 0v.282c.226-.079.496-.17.79-.26C4.606.272 5.67 0 6.5 0c.84 0 1.524.277 2.121.519l.043.018C9.286.788 9.828 1 10.5 1c.7 0 1.638-.23 2.437-.477a20 20 0 0 0 1.349-.476l.019-.007.004-.002h.001M14 1.221c-.22.078-.48.167-.766.255-.81.252-1.872.523-2.734.523-.886 0-1.592-.286-2.203-.534l-.008-.003C7.662 1.21 7.139 1 6.5 1c-.669 0-1.606.229-2.415.478A21 21 0 0 0 3 1.845v6.433c.22-.078.48-.167.766-.255C4.576 7.77 5.638 7.5 6.5 7.5c.847 0 1.548.28 2.158.525l.028.01C9.32 8.29 9.86 8.5 10.5 8.5c.668 0 1.606-.229 2.415-.478A21 21 0 0 0 14 7.655V1.222z" />
            </svg>Road Reports</a>
        </li>

        <li>
          <a href="../lgu_officer_module/citizen_reports_view.php" class="nav-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-people" viewBox="0 0 16 16">
              <path d="M15 14s1 0 1-1h-3.5a2.5 2.5 0 0 0-2.5-2.5H12a2.5 2.5 0 0 0-2.5 2.5H6a2.5 2.5 0 0 0-2.5-2.5H3.5a2.5 2.5 0 0 0-2.5 2.5H1v1a1 1 0 0 0 1 1h2.5a1.5 1.5 0 0 1 1.5v2a1.5 1.5 0 0 1-1.5 1.5H2a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h14zm-7.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708-.708l-3-3a.5.5 0 0 1 0-.708zM5.5 8a.5.5 0 0 1 .5-.5V5a2 2 0 0 1 2-2H15a2 2 0 0 1 2 2v2.5a.5.5 0 0 1-.5.5h-6a.5.5 0 0 1-.5-.5V8a2 2 0 0 1 2-2H8a2 2 0 0 1-2 2v2.5a.5.5 0 0 1-.5.5h-6z"/>
              <path d="M1 2.5a1.5 1.5 0 0 1 1.5-1.5h3a1.5 1.5 0 0 1 1.5 1.5h3a1.5 1.5 0 0 1 1.5-1.5V1a1 1 0 0 0-1-1H1.5A1.5 1.5 0 0 0 0 2.5z"/>
            </svg>Citizen Reports</a>
        </li>

        <li>
          <a href="../lgu_officer_module/inspection_management.php" class="nav-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-graph-up-arrow" viewBox="0 0 16 16">
              <path fill-rule="evenodd" d="M0 0h1v15h15v1H0zm10 3.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-1 0V4.9l-3.613 4.417a.5.5 0 0 1-.74.037L7.06 6.767l-3.656 5.027a.5.5 0 0 1-.808-.588l4-5.5a.5.5 0 0 1 .758-.06l2.609 2.61L13.445 4H10.5a.5.5 0 0 1-.5-.5" />
            </svg>Inspections</a>
        </li>






      <li>
        <a href="../lgu_officer_module/gis_overview.php" class="nav-link"><svg
            xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-geo-alt"
            viewBox="0 0 16 16">
            <path
              d="M12.166 8.94c-.524 1.062-1.234 2.12-1.96 3.07A32 32 0 0 1 8 14.58a32 32 0 0 1-2.206-2.57c-.726-.95-1.436-2.008-1.96-3.07C3.304 7.867 3 6.862 3 6a5 5 0 0 1 10 0c0 .862-.305 1.867-.834 2.94M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10" />
            <path d="M8 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4m0 1a3 3 0 1 0 0-6 3 3 0 0 0 0 6" />
          </svg>GIS Mapping</a>
      </li>


      <li>
        <a href="../lgu_officer_module/document_management.php" class="nav-link"><svg
            xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-clipboard2-data"
            viewBox="0 0 16 16">
            <path
              d="M9.5 0a.5.5 0 0 1 .5.5.5.5 0 0 0 .5.5.5.5 0 0 1 .5.5V2a.5.5 0 0 1-.5.5h-5A.5.5 0 0 1 5 2v-.5a.5.5 0 0 1 .5-.5.5.5 0 0 0 .5-.5.5.5 0 0 1 .5-.5z" />
            <path
              d="M3 2.5a.5.5 0 0 1 .5-.5H4a.5.5 0 0 0 0-1h-.5A1.5 1.5 0 0 0 2 2.5v12A1.5 1.5 0 0 0 3.5 16h9a1.5 1.5 0 0 0 1.5-1.5v-12A1.5 1.5 0 0 0 12.5 1H12a.5.5 0 0 0 0 1h.5a.5.5 0 0 1 .5.5v12a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5z" />
            <path
              d="M10 7a1 1 0 1 1 2 0v5a1 1 0 1 1-2 0zm-6 4a1 1 0 1 1 2 0v1a1 1 0 1 1-2 0zm4-3a1 1 0 0 0-1 1v3a1 1 0 1 0 2 0V9a1 1 0 0 0-1-1" />
          </svg>Documents</a>
      </li>


      <?php if ($auth->isLguOfficer() || $auth->isAdmin()): ?>
        <li>
          <a href="../lgu_officer_module/public_transparency.php" class="nav-link"><svg
              xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-transparency"
              viewBox="0 0 16 16">
              <path
                d="M0 6.5a6.5 6.5 0 0 1 12.346-2.846 6.5 6.5 0 1 1-8.691 8.691A6.5 6.5 0 0 1 0 6.5m5.144 6.358a5.5 5.5 0 1 0 7.714-7.714 6.5 6.5 0 0 1-7.714 7.714m-.733-1.269q.546.226 1.144.33l-1.474-1.474q.104.597.33 1.144m2.614.386a5.5 5.5 0 0 0 1.173-.242L4.374 7.91a6 6 0 0 0-.296 1.118zm2.157-.672q.446-.25.838-.576L5.418 6.126a6 6 0 0 0-.587.826zm1.545-1.284q.325-.39.576-.837L6.953 4.83a6 6 0 0 0-.827.587l4.6 4.602Zm1.006-1.822q.183-.562.242-1.172L9.028 4.078q-.58.096-1.118.296l3.823 3.824Zm.186-2.642a5.5 5.5 0 0 0-.33-1.144 5.5 5.5 0 0 0-1.144-.33z" />
            </svg>Transparency</a>
        </li>

        <li>
          <a href="../lgu_officer_module/publication_management.php" class="nav-link"><svg
              xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-newspaper"
              viewBox="0 0 16 16">
              <path d="M0 1.5A1.5 1.5 0 0 1 1.5 0h8A1.5 1.5 0 0 1 11 1.5v.538h.538c.673 0 1.218.545 1.218 1.218v2.625c0 .673-.545 1.218-1.218 1.218h-.538V13.5A1.5 1.5 0 0 1 9.5 15h-8A1.5 1.5 0 0 1 0 13.5zM1 2.5v11h7.5a.5.5 0 0 0 .5-.5v-11a.5.5 0 0 0-.5-.5h-8a.5.5 0 0 0-.5.5m8 6.5h1v1h-1zm0-2h1v1h-1zm0-2h1v1h-1zm0-2h1v1h-1z"/>
            </svg>Publications</a>
        </li>

      <?php endif; ?>


    </ul>

    <div class="sidebar-footer">
      <div class="sidebar-user-info">
        <p class="user-name">LGU Officer</p>
        <p class="user-email">officer@lgu.gov.ph</p>
      </div>
      <a href="../logout.php" class="logout-btn">Logout</a>
    </div>
  </div>
</nav>

<style>
  @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap");

  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Poppins", sans-serif;
  }

  body {
    min-height: 100vh;
  }

  .sidebar-nav {
    display: flex;
    flex-direction: column;
  }

  .sidebar-content {
    flex: 1;
    overflow-y: auto;
  }

  body {
    background: url("<?php echo $asset_base; ?>cityhall.jpeg") center/cover no-repeat fixed;
    position: relative;
  }


  body::before {
    content: "";
    position: absolute;
    inset: 0;
    backdrop-filter: blur(6px);
    background: rgba(0, 0, 0, 0.35);
    z-index: 0;
  }

  /* SIDEBAR */
  .sidebar-nav {
    position: fixed;
    width: 250px;
    height: 100vh;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(18px);
    box-shadow: 0 4px 25px rgba(0, 0, 0, 0.25);
    z-index: 1000;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }

  .site-logo {
    padding: 20px;
    text-align: center;
  }

  .site-logo img {
    width: 120px;
    border-radius: 10px;
  }

  .nav-list {
    list-style: none;
    padding: 0 20px;
  }

  .nav-link {
    display: block;
    padding: 12px 20px;
    color: #000;
    text-decoration: none;
    border-radius: 8px;
    margin-bottom: 6px;
    transition: 0.3s;
  }

  .nav-link:hover {
    background: #97a4c2;
    transform: translateX(5px);
  }

  .nav-link.active {
    background: #3762c8;
    color: #fff;
  }

  .sidebar-user-info {
    width: 100%;
    box-sizing: border-box;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    padding: 0.75rem 0px;
    font-size: 0.875rem;
    text-align: center;
  }

  .logout-btn {
    margin-top: 8px;
    padding: 8px 14px;
    background: #3762c8;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
  }

  .nav-link {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .sidebar-footer {
    display: flex;
    flex-direction: column;
    align-items: center;
  }
</style>