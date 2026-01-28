<?php
// Start session and include authentication
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}
require_once '../config/auth.php';

// Path detection for assets
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$is_subfolder = ($current_dir !== 'road_and_infra_dept');
$is_citizen_module = ($current_dir === 'citizen_module');
$asset_base = $is_subfolder ? '../user_and_access_management_module/assets/img/' : 'user_and_access_management_module/assets/img/';


// Require login to access this page
$auth->requireLogin();
?>

<nav class="sidebar-nav">
  <div class="sidebar-content">
    <div class="site-logo">
      <img src="<?php echo $asset_base; ?>logocityhall.png" />
      <p class="user-role">Citizen Portal</p>
    </div>

    <ul class="sidebar-menu">
        <li>
          <a href="../citizen_module/index.php" class="nav-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-speedometer2" viewBox="0 0 16 16">
              <path d="M8 4a.5.5 0 0 1 .5.5V6a.5.5 0 0 1-1 0V4.5A.5.5 0 0 1 8 4M3.732 5.732a.5.5 0 0 1 .707 0l.915.914a.5.5 0 1 1-.708.708l-.914-.915a.5.5 0 0 1 0-.707M2 10a.5.5 0 0 1 .5-.5h1.586a.5.5 0 0 1 0 1H2.5A.5.5 0 0 1 2 10m9.5 0a.5.5 0 0 1 .5-.5h1.5a.5.5 0 0 1 0 1H12a.5.5 0 0 1-.5-.5m.754-4.246a.389.389 0 0 0-.527-.02L7.547 9.31a.91.91 0 1 0 1.302 1.258l3.434-4.297a.389.389 0 0 0-.029-.518z"/>
              <path fill-rule="evenodd" d="M0 10a8 8 0 1 1 15.547 2.661c-.442 1.253-1.845 1.602-2.932 1.25l-1.173-.378a15 15 0 0 0-1.071-.306l-1.037-.255c-.458-.113-.923-.23-1.396-.35l-.547-.14c-.643-.163-1.293-.331-1.953-.503L5.47 12.112c-.503-.13-.945-.251-1.341-.36a15 15 0 0 0-1.021-.247l-1.096-.217c-1.095-.218-1.513-.733-1.127-1.977A7.97 7.97 0 0 1 0 10m8 6c.074 0 .148-.004.22-.012l.004-.056a5 5 0 0 0-.15-.864L7.404 14.8l-.007.031c-.167.763.585 1.168 1.14 1.168.077 0 .15-.008.216-.017l.005-.058a7 7 0 0 0-.15-.98l-.013-.056c-.164-.717.585-1.134 1.184-1.134.144 0 .283.021.415.059l.044.016a5.8 5.8 0 0 0 .15-.945l.003-.06c.086-1.567-1.887-1.186-1.993-2.503-.072-1.109 1.218-2.215 2.15-2.215.177 0 .35.039.512.116l.044.021c.3.151.446.471.63.777.282.47.614.748 1.033.748q.123 0 .257-.025l.045-.01c.445-.102.639-.309.673-.535l.006-.062c.072-1.353-1.503-1.623-1.503-2.937 0-.327.101-.694.301-1.022l.026-.044c.31-.519.852-.617 1.293-.617q.13 0 .252.017l.043.006c.419.051.737.227.828.592l.007.058c.205 1.543-1.978 1.345-2.122 2.746-.073 1.16 1.109 2.112 2.154 2.112.152 0 .297-.02.435-.058l.041-.01c.433-.12.63-.33.675-.527l.003-.045c.213-1.482-2.094-1.27-2.094-2.729 0-1.173 1.291-2.203 2.323-2.203a3 3 0 0 1 .684.081 33 33 0 0 1 .69 3.038c.19 1.378-.461 2.532-1.325 3.506l-.044.05c-.341.388-.736.718-1.144 1.013l-.014.01c-.407.298-.823.556-1.24.773l-.045.023c-.377.193-.759.354-1.129.485l-.08.028a5 5 0 0 1-1.182.266 8 8 0 0 1-1.585-.051l-.04-.005c-.404-.05-.813-.132-1.217-.245l-.015-.005c-.402-.11-.803-.249-1.198-.415l-.044-.019c-.352-.15-.698-.319-1.03-.505l-.014-.008c-.406-.23-.793-.489-1.155-.776l-.012-.01c-.367-.291-.708-.61-1.019-.958l-.02-.02c-.352-.396-.656-.832-.899-1.301l-.012-.025a6 6 0 0 1-.582-1.554l-.009-.045c-.13-.672-.188-1.348-.172-2.025a6 6 0 0 1 .132-1.198z"/>
            </svg>Dashboard</a>
        </li>

        <li>
          <a href="../citizen_module/public_transparency_view.php" class="nav-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-transparency" viewBox="0 0 16 16">
              <path d="M0 6.5a6.5 6.5 0 0 1 12.346-2.846 6.5 6.5 0 1 1-8.691 8.691A6.5 6.5 0 0 1 0 6.5m5.144 6.358a5.5 5.5 0 1 0 7.714-7.714 6.5 6.5 0 0 1-7.714 7.714m-.733-1.269q.546.226 1.144.33l-1.474-1.474q.104.597.33 1.144m2.614.386a5.5 5.5 0 0 0 1.173-.242L4.374 7.91a6 6 0 0 0-.296 1.118zm2.157-.672q.446-.25.838-.576L5.418 6.126a6 6 0 0 0-.587.826zm1.545-1.284q.325-.39.576-.837L6.953 4.83a6 6 0 0 0-.827.587l4.6 4.602Zm1.006-1.822q.183-.562.242-1.172L9.028 4.078q-.58.096-1.118.296l3.823 3.824Zm.186-2.642a5.5 5.5 0 0 0-.33-1.144 5.5 5.5 0 0 0-1.144-.33z" />
            </svg>Transparency</a>
        </li>

        <li>
          <a href="../citizen_module/gis_mapping_view.php" class="nav-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-geo-alt" viewBox="0 0 16 16">
              <path d="M12.166 8.94c-.524 1.062-1.234 2.12-1.96 3.07A32 32 0 0 1 8 14.58a32 32 0 0 1-2.206-2.57c-.726-.95-1.436-2.008-1.96-3.07C3.304 7.867 3 6.862 3 6a5 5 0 0 1 10 0c0 .862-.305 1.867-.834 2.94M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10" />
              <path d="M8 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4m0 1a3 3 0 1 0 0-6 3 3 0 0 0 0 6" />
            </svg>GIS Mapping</a>
        </li>

        <li>
          <a href="../citizen_module/report_damage.php" class="nav-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-exclamation-triangle" viewBox="0 0 16 16">
              <path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.146.146 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.163.163 0 0 1-.054.06.116.116 0 0 1-.066.017H1.146a.115.115 0 0 1-.066-.017.163.163 0 0 1-.054-.06.176.176 0 0 1 .002-.183L7.884 2.073a.147.147 0 0 1 .054-.057zm1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767z"/>
              <path d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
            </svg>Report Damage</a>
        </li>
    </ul>

    <div class="sidebar-footer">
      <div class="sidebar-user-info" 
           data-user-info='<?php echo json_encode([
               'id' => $_SESSION['user_id'] ?? 0,
               'first_name' => $_SESSION['first_name'] ?? '',
               'last_name' => $_SESSION['last_name'] ?? '',
               'email' => $_SESSION['email'] ?? '',
               'role' => $_SESSION['role'] ?? ''
           ]); ?>'>
        <p class="user-name"><?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Citizen'); ?></p>
        <p class="user-email"><?php echo htmlspecialchars($_SESSION['email'] ?? 'citizen@lgu.gov.ph'); ?></p>
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
