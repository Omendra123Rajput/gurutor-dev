<?php include get_theme_file_path('template-parts/includes/dashboard_main_tab_style.php'); ?>

<div class="bc_main_tabs" style="position: relative;right: 50px;top: -10px;width: 115%;">
    <div class="bc_main_tab" data-tab="dashboard">DASHBOARD</div>
    <div class="bc_main_tab active" data-tab="statistics">STATISTICS</div>
    <div class="bc_main_tab" data-tab="overview">OVERVIEW</div>
</div>

<div class="bc_main_content" id="dashboard">
    <h2>Dashboard Content</h2>
    <p>Details for Dashboard...</p>
</div>
<div class="bc_main_content active" id="statistics">
    <?php include get_theme_file_path('template-parts/statistics/index.php'); ?>
</div>
<div class="bc_main_content" id="overview">
    <h2>Overview Content</h2>
    <p>Details for Overview...</p>
</div>

<script>
    const tabs_main = document.querySelectorAll(".bc_main_tab");
    const contents_main = document.querySelectorAll(".bc_main_content");

    tabs_main.forEach(tab => {
        tab.addEventListener("click", () => {
            // Remove active classes
            tabs_main.forEach(t => t.classList.remove("active"));
            contents_main.forEach(c => c.classList.remove("active"));

            // Add active to current
            tab.classList.add("active");
            const target = tab.getAttribute("data-tab");
            document.getElementById(target).classList.add("active");
        });
    });
</script>
