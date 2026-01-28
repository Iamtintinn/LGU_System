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
      <p class="user-role">Engineer</p>
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
          <a href="damage_assessment.php" class="nav-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-calculator" viewBox="0 0 16 16">
              <path d="M1 2.5a1 1 0 0 1 1-1h11a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1v-11zm1 0v11h11v-11H2z"/>
              <path d="M4 3.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5h-7a.5.5 0 0 1-.5-.5v-2zm0 4a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm0 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm3-6a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm0 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm0 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm3-6a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm0 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-4z"/>
            </svg>Damage Assessment & Cost Estimation</a>
        </li>

        <li>
          <a href="inspection_workflow.php" class="nav-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-clipboard-check" viewBox="0 0 16 16">
              <path fill-rule="evenodd" d="M10.854 7.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 9.793l2.646-2.647a.5.5 0 0 1 .708 0z"/>
              <path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1v-1z"/>
              <path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h3zm-3-1A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3z"/>
            </svg>Inspection & Workflow</a>
        </li>

        <li>
          <a href="publications_view.php" class="nav-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-newspaper" viewBox="0 0 16 16">
              <path d="M0 1.5A1.5 1.5 0 0 1 1.5 0h8A1.5 1.5 0 0 1 11 1.5v.383c.072.004.146.01.22.015.204.01.438.02.675.058.578.095 1.27.383 1.627.965.264.437.365.979.408 1.523c.041.528.015 1.065-.02 1.523-.035.46-.086.88-.144 1.219-.029.17-.058.313-.082.418l-.013.057c-.04.176-.087.342-.146.498a.5.5 0 0 1-.146.15c-.166.13-.357.2-.558.2H1.5a.5.5 0 0 1-.5-.5V3H0v2.5A1.5 1.5 0 0 0 1.5 7h12.099c.331 0 .647-.068.925-.197.28-.13.527-.33.717-.588.191-.26.34-.576.425-.924.084-.349.14-.738.177-1.14.037-.4.05-.793.045-1.169-.005-.376-.028-.724-.082-1.032-.053-.309-.134-.6-.245-.872C15.29 2.433 14.86 1.9 14.2 1.735c-.335-.083-.663-.118-.945-.134-.146-.008-.282-.01-.398-.01H1.5zm0 1h-.5v9h11.1c.181 0 .354-.03.511-.088.157-.058.298-.149.411-.264.113-.115.199-.26.256-.447.058-.187.103-.414.134-.672.03-.258.045-.525.042-.785-.003-.26-.023-.511-.063-.744-.04-.233-.1-.438-.18-.607-.08-.17-.186-.31-.317-.408-.13-.098-.283-.167-.453-.207-.17-.04-.358-.06-.558-.068-.2-.007-.398-.01-.595-.006H1.5z"/>
              <path d="M2 3h10v2H2V3zm0 3h4v1H2V6zm0 2h4v1H2V8zm0 2h4v1H2v-1zm5-6h2v1H7V6zm0 2h2v1H7V8zm0 2h2v1H7v-1zm0 2h2v1H7v-1z"/>
            </svg>Publications
            <span class="notification-badge" id="publicationCount">0</span>
          </a>
        </li>
    </ul>

    <!-- Publications Sidebar Widget -->
    <div class="publications-widget">
        <h3 style="color: #3762c8; font-size: 0.9rem; margin-bottom: 10px; padding: 0 20px;">
            <i class="fas fa-newspaper"></i> Recent Publications
        </h3>
        <div id="recentPublications" style="padding: 0 20px;">
            <div style="text-align: center; padding: 15px 0; color: #64748b;">
                <i class="fas fa-spinner fa-spin" style="font-size: 1.2rem; margin-bottom: 5px; display: block;"></i>
                Loading publications...
            </div>
        </div>
    </div>

    <div class="sidebar-footer">
      <div class="sidebar-user-info">
        <p class="user-name">Engineer</p>
        <p class="user-email">engineer@lgu.gov.ph</p>
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

  /* Notification Badge */
  .notification-badge {
    background: #ef4444;
    color: white;
    font-size: 0.7rem;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: auto;
    font-weight: 600;
    min-width: 18px;
    text-align: center;
  }

  /* Publications Widget */
  .publications-widget {
    background: rgba(255, 255, 255, 0.1);
    margin: 15px 20px;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.2);
  }

  .publication-item {
    background: rgba(255, 255, 255, 0.8);
    padding: 10px;
    margin-bottom: 8px;
    border-radius: 6px;
    border-left: 3px solid #3762c8;
    cursor: pointer;
    transition: all 0.3s ease;
  }

  .publication-item:hover {
    background: rgba(255, 255, 255, 0.95);
    transform: translateX(3px);
  }

  .publication-item:last-child {
    margin-bottom: 0;
  }

  .publication-title {
    font-size: 0.8rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 3px;
    line-height: 1.2;
  }

  .publication-meta {
    font-size: 0.7rem;
    color: #64748b;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .publication-status {
    background: #10b981;
    color: white;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.6rem;
    font-weight: 600;
  }

  .no-publications {
    text-align: center;
    color: #64748b;
    font-size: 0.8rem;
    padding: 15px 0;
  }

  .sidebar-footer {
    display: flex;
    flex-direction: column;
    align-items: center;
  }
</style>

<script>
// Load publications from database
function loadPublications() {
    fetch('../lgu_officer_module/api/get_publications.php')
        .then(response => response.json())
        .then(data => {
            displayPublications(data);
            updatePublicationCount(data.length);
        })
        .catch(error => {
            console.error('Error loading publications:', error);
            displayError();
        });
}

// Display publications in sidebar widget
function displayPublications(publications) {
    const container = document.getElementById('recentPublications');
    
    if (!publications || publications.length === 0) {
        container.innerHTML = '<div class="no-publications">No publications available</div>';
        return;
    }
    
    // Show only the 3 most recent publications
    const recentPublications = publications.slice(0, 3);
    
    container.innerHTML = recentPublications.map(pub => `
        <div class="publication-item" onclick="viewPublication(${pub.id})">
            <div class="publication-title">${escapeHtml(pub.road_name)} - ${escapeHtml(pub.issue_summary.substring(0, 30))}...</div>
            <div class="publication-meta">
                <span>${formatDate(pub.publication_date)}</span>
                <span class="publication-status">${formatStatus(pub.status_public)}</span>
            </div>
        </div>
    `).join('');
}

// Update publication count badge
function updatePublicationCount(count) {
    const badge = document.getElementById('publicationCount');
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline-block' : 'none';
    }
}

// Display error message
function displayError() {
    const container = document.getElementById('recentPublications');
    container.innerHTML = '<div class="no-publications">Unable to load publications</div>';
}

// View publication details
function viewPublication(publicationId) {
    // Redirect to publications view page with specific publication
    window.location.href = `publications_view.php?id=${publicationId}`;
}

// Format date for display
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays === 0) {
        return 'Today';
    } else if (diffDays === 1) {
        return 'Yesterday';
    } else if (diffDays < 7) {
        return `${diffDays} days ago`;
    } else {
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }
}

// Format status for display
function formatStatus(status) {
    return status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Load publications when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadPublications();
    
    // Auto-refresh publications every 30 seconds
    setInterval(loadPublications, 30000);
});

// Listen for custom publication update events
window.addEventListener('publicationUpdate', function() {
    loadPublications();
});
</script>
