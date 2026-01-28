<?php
// Start session and include authentication
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}
require_once '../config/auth.php';

// Path detection for assets
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$is_subfolder = ($current_dir !== 'road_and_infra_dept');
$asset_base = $is_subfolder ? '../user_and_access_management_module/assets/img/' : 'user_and_access_management_module/assets/img/';

// Require login to access this page
$auth->requireLogin();
?>

<nav class="sidebar-nav">
  <div class="sidebar-content">
    <div class="site-logo">
      <img src="<?php echo $asset_base; ?>logocityhall.png" />
      <p class="user-role">Administrator</p>
    </div>

    <ul class="sidebar-menu">
      <li>
        <a href="index.php" class="nav-link">
          <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-speedometer2" viewBox="0 0 16 16">
            <path d="M8 4a.5.5 0 0 1 .5.5V6a.5.5 0 0 1-1 0V4.5A.5.5 0 0 1 8 4M3.732 5.732a.5.5 0 0 1 .707 0l.915.914a.5.5 0 1 1-.708.708l-.914-.915a.5.5 0 0 1 0-.707M2 10a.5.5 0 0 1 .5-.5h1.586a.5.5 0 0 1 0 1H2.5A.5.5 0 0 1 2 10m9.5 0a.5.5 0 0 1 .5-.5h1.5a.5.5 0 0 1 0 1H12a.5.5 0 0 1-.5-.5m.754-4.246a.389.389 0 0 0-.527-.02L7.547 9.31a.91.91 0 1 0 1.302 1.258l3.434-4.297a.389.389 0 0 0-.029-.518z"/>
            <path fill-rule="evenodd" d="M0 10a8 8 0 1 1 15.547 2.661c-.442 1.253-1.845 1.602-2.932 1.25l-1.173-.378a15 15 0 0 0-1.071-.306l-1.037-.255c-.458-.113-.923-.23-1.396-.35l-.547-.14c-.643-.163-1.293-.331-1.953-.503L5.47 12.112c-.503-.13-.945-.251-1.341-.36a15 15 0 0 0-1.021-.247l-1.096-.217c-1.095-.218-1.513-.733-1.127-1.977A7.97 7.97 0 0 1 0 10m8 6c.074 0 .148-.004.22-.012l.004-.056a5 5 0 0 0-.15-.864L7.404 14.8l-.007.031c-.167.763.585 1.168 1.14 1.168.077 0 .15-.008.216-.017l.005-.058a7 7 0 0 0-.15-.98l-.013-.056c-.164-.717.585-1.134 1.184-1.134.144 0 .283.021.415.059l.044.016a5.8 5.8 0 0 0 .15-.945l.003-.06c.086-1.567-1.887-1.186-1.993-2.503-.072-1.109 1.218-2.215 2.15-2.215.177 0 .35.039.512.116l.044.021c.3.151.446.471.63.777.282.47.614.748 1.033.748q.123 0 .257-.025l.045-.01c.445-.102.639-.309.673-.535l.006-.062c.072-1.353-1.503-1.623-1.503-2.937 0-.327.101-.694.301-1.022l.026-.044c.31-.519.852-.617 1.293-.617q.13 0 .252.017l.043.006c.419.051.737.227.828.592l.007.058c.205 1.543-1.978 1.345-2.122 2.746-.073 1.16 1.109 2.112 2.154 2.112.152 0 .297-.02.435-.058l.041-.01c.433-.12.63-.33.675-.527l.003-.045c.213-1.482-2.094-1.27-2.094-2.729 0-1.173 1.291-2.203 2.323-2.203a3 3 0 0 1 .684.081 33 33 0 0 1 .69 3.038c.19 1.378-.461 2.532-1.325 3.506l-.044.05c-.341.388-.736.718-1.144 1.013l-.014.01c-.407.298-.823.556-1.24.773l-.045.023c-.377.193-.759.354-1.129.485l-.08.028a5 5 0 0 1-1.182.266 8 8 0 0 1-1.585-.051l-.04-.005c-.404-.05-.813-.132-1.217-.245l-.015-.005c-.402-.11-.803-.249-1.198-.415l-.044-.019c-.352-.15-.698-.319-1.03-.505l-.014-.008c-.406-.23-.793-.489-1.155-.776l-.012-.01c-.367-.291-.708-.61-1.019-.958l-.02-.02c-.352-.396-.656-.832-.899-1.301l-.012-.025a6 6 0 0 1-.582-1.554l-.009-.045c-.13-.672-.188-1.348-.172-2.025a6 6 0 0 1 .132-1.198z"/>
          </svg>Dashboard</a>
      </li>

      <li>
        <a href="permissions.php" class="nav-link">
          <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-people" viewBox="0 0 16 16">
            <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1zm-7.978-1L7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002-.014.002zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0M6.936 9.28a6 6 0 0 0-1.23-.247A7 7 0 0 0 5 9c-4 0-5 3-5 4 0 .667.333 1 1 1h4.216A2.24 2.24 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816M4.92 10A5.5 5.5 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0m3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4"/>
          </svg>User and Access Management</a>
      </li>

      <li>
        <a href="users.php" class="nav-link">
          <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-person-check" viewBox="0 0 16 16">
            <path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7m1.679-4.493-1.335 2.226a.75.75 0 0 1-1.174.144l-.774-.773a.5.5 0 0 1 .708-.708l.547.548 1.17-1.951a.5.5 0 1 1 .858.514M11 5a3 3 0 1 1-6 0 3 3 0 0 1 6 0M8 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4"/>
            <path d="M8.256 14a4.5 4.5 0 0 1-.229-1.004H3c.001-.246.154-.986.832-1.664C4.484 10.68 5.711 10 8 10c.26 0 .507.009.74.025.226-.341.496-.65.804-.918Q8.844 9.002 8 9c-5 0-6 3-6 4s1 1 1 1z"/>
          </svg>Registered</a>
      </li>
    </ul>

    <div class="sidebar-footer">
      <div class="sidebar-user-info">
        <p class="user-name"><?php echo htmlspecialchars($auth->getUserFullName(), ENT_QUOTES, 'UTF-8'); ?></p>
        <p class="user-email"><?php echo htmlspecialchars($auth->getUserEmail(), ENT_QUOTES, 'UTF-8'); ?></p>
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

  .sidebar-nav {
    display: flex;
    flex-direction: column;
  }

  .sidebar-content {
    flex: 1;
    overflow-y: auto;
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
    top: 0;
    left: 0;
  }

  .site-logo {
    padding: 20px;
    text-align: center;
  }

  .site-logo img {
    width: 120px;
    border-radius: 10px;
  }

  .sidebar-menu {
    list-style: none;
    padding: 0 20px;
  }

  .nav-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 20px;
    color: #333;
    text-decoration: none;
    border-radius: 8px;
    margin-bottom: 6px;
    transition: 0.3s;
    font-size: 0.9rem;
    font-weight: 500;
  }

  .nav-link:hover {
    background: #e9ecef;
    transform: translateX(5px);
    color: #3762c8;
  }

  .nav-link.active {
    background: #3762c8;
    color: #fff;
  }

  .sidebar-footer {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px;
    border-top: 1px solid rgba(0,0,0,0.05);
  }

  .sidebar-user-info {
    width: 100%;
    margin-bottom: 12px;
    text-align: center;
  }

  .user-name {
    font-weight: 600;
    color: #333;
    font-size: 0.85rem;
  }

  .user-email {
    font-size: 0.75rem;
    color: #666;
  }

  .user-role {
    font-size: 0.8rem;
    font-weight: 600;
    color: #3762c8;
    margin-top: 5px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .logout-btn {
    padding: 8px 20px;
    background: #3762c8;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 500;
    transition: 0.2s;
  }

  .logout-btn:hover {
    background: #2a4fa3;
    box-shadow: 0 4px 12px rgba(55, 98, 200, 0.2);
  }
</style>
