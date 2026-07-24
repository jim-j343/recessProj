package forum.controllers;

import forum.api.ApiClient;
import forum.api.dto.AdminDashboardDto;
import forum.api.dto.AdminMemberDto;
import forum.app.SceneManager;
import forum.app.Session;
import forum.models.User;
import forum.services.AuthService;
import forum.util.NavbarHelper;

import javafx.application.Platform;
import javafx.beans.property.SimpleStringProperty;
import javafx.collections.FXCollections;
import javafx.fxml.FXML;
import javafx.scene.control.*;

public class AdminDashboardController {

    @FXML private Label      avatarLabel;
    @FXML private Label      userNameLabel;
    @FXML private MenuButton notifButton;
    @FXML private Label      notifBadge;
    @FXML private Label      totalMembersLabel;
    @FXML private Label      activeTodayLabel;
    @FXML private Label      warnedLabel;
    @FXML private Label      blacklistedLabel;
    @FXML private Label      statusLabel;

    // Group settings table (mirrors web HTML table)
    @FXML private TableView<AdminDashboardDto.GroupSetting> groupSettingsTable;
    @FXML private TableColumn<AdminDashboardDto.GroupSetting, String> colGroup;
    @FXML private TableColumn<AdminDashboardDto.GroupSetting, String> colCourse;
    @FXML private TableColumn<AdminDashboardDto.GroupSetting, String> colMembers;
    @FXML private TableColumn<AdminDashboardDto.GroupSetting, String> colWarnDays;
    @FXML private TableColumn<AdminDashboardDto.GroupSetting, String> colBlkDays;

    // Member table
    @FXML private TableView<AdminMemberDto> membersTable;
    @FXML private TableColumn<AdminMemberDto, String> colMember;
    @FXML private TableColumn<AdminMemberDto, String> colLastActive;
    @FXML private TableColumn<AdminMemberDto, String> colPosts;
    @FXML private TableColumn<AdminMemberDto, String> colStatus;

    private final ApiClient api = new ApiClient();

    @FXML
    private void initialize() {
        // Populate nav-user chip from session
        User user = Session.currentUser();
        if (user != null) {
            userNameLabel.setText(user.displayName());
            // Single first-letter initial to match web x-avatar component
            avatarLabel.setText(initial(user.displayName()));
        }

        // Load real notifications from backend
        NavbarHelper.loadNotifications(api, notifButton, notifBadge);

        // ── Group settings table ──────────────────────────────────────
        colGroup.setCellValueFactory(c ->
                new SimpleStringProperty(safe(c.getValue().name, "—")));
        colCourse.setCellValueFactory(c ->
                new SimpleStringProperty(safe(c.getValue().courseName, "—")));
        colMembers.setCellValueFactory(c ->
                new SimpleStringProperty(String.valueOf(c.getValue().membersCount)));
        colWarnDays.setCellValueFactory(c ->
                new SimpleStringProperty(c.getValue().inactivityWarningDays + " days"));
        colBlkDays.setCellValueFactory(c ->
                new SimpleStringProperty(c.getValue().blacklistDurationDays + " days"));

        // ── Member table ──────────────────────────────────────────────
        colMember.setCellValueFactory(c ->
                new SimpleStringProperty(memberName(c.getValue())));
        // Render group name in bold to match web
        colGroup.setCellFactory(column -> new TableCell<AdminDashboardDto.GroupSetting, String>() {
            @Override
            protected void updateItem(String name, boolean empty) {
                super.updateItem(name, empty);
                if (empty || name == null) {
                    setGraphic(null); setText(null);
                } else {
                    Label lbl = new Label(name);
                    lbl.setStyle("-fx-font-weight: 800; -fx-font-size: 13px; -fx-text-fill: #111827;");
                    setGraphic(lbl);
                    setText(null);
                }
            }
        });

        // Render member name in bold to match web
        colMember.setCellFactory(column -> new TableCell<AdminMemberDto, String>() {
            @Override
            protected void updateItem(String name, boolean empty) {
                super.updateItem(name, empty);
                if (empty || name == null) {
                    setGraphic(null); setText(null);
                } else {
                    Label lbl = new Label(name);
                    lbl.setStyle("-fx-font-weight: 800; -fx-font-size: 13px; -fx-text-fill: #111827;");
                    setGraphic(lbl);
                    setText(null);
                }
            }
        });
        colLastActive.setCellValueFactory(c ->
                new SimpleStringProperty(safe(c.getValue().lastActiveHuman, "Never")));
        colPosts.setCellValueFactory(c ->
                new SimpleStringProperty(String.valueOf(c.getValue().postsCount)));
        colStatus.setCellValueFactory(c -> new SimpleStringProperty(c.getValue().status));
        colStatus.setCellFactory(column -> new TableCell<AdminMemberDto, String>() {
            @Override
            protected void updateItem(String status, boolean empty) {
                super.updateItem(status, empty);
                if (empty || status == null) {
                    setGraphic(null);
                    setText(null);
                } else {
                    AdminMemberDto m = getTableRow().getItem();
                    if (m != null) {
                        Label badge = new Label(memberStatus(m));
                        badge.getStyleClass().add("badge");
                        if ("blacklisted".equalsIgnoreCase(m.status)) {
                            badge.getStyleClass().add("badge-danger");
                        } else if (m.unheededWarningCount > 0) {
                            badge.getStyleClass().add("badge-warning");
                        } else {
                            badge.getStyleClass().add("badge-success");
                        }
                        setGraphic(badge);
                        setText(null);
                    } else {
                        setGraphic(null);
                        setText(null);
                    }
                }
            }
        });

        // Right-click context menu on member rows for quick blacklist/lift
        membersTable.setRowFactory(table -> memberRow());

<<<<<<< HEAD
        // ── Auto-resize tables (Fixed height to match web style) ──
        groupSettingsTable.setFixedCellSize(52);
        groupSettingsTable.prefHeightProperty().bind(
            javafx.beans.binding.Bindings.max(1, javafx.beans.binding.Bindings.size(groupSettingsTable.getItems()))
                .multiply(groupSettingsTable.getFixedCellSize()).add(36)
        );

        membersTable.setFixedCellSize(52);
        membersTable.prefHeightProperty().bind(
            javafx.beans.binding.Bindings.max(1, javafx.beans.binding.Bindings.size(membersTable.getItems()))
                .multiply(membersTable.getFixedCellSize()).add(36)
        );

=======
>>>>>>> c0a0fe073da5b40940d7bd0bb2ce0c10d655d5ed

        loadDashboard();
    }

    private void loadDashboard() {
        String token = Session.authToken();
        if (token == null || token.isBlank()) {
            showStatus("Admin data requires an online web-app session.");
            return;
        }

        Thread worker = new Thread(() -> {
            try {
                AdminDashboardDto data = api.adminDashboard(token);
                Platform.runLater(() -> render(data));
            } catch (Exception e) {
                if (e instanceof InterruptedException) Thread.currentThread().interrupt();
                Platform.runLater(() -> showStatus("Could not load admin dashboard: " + e.getMessage()));
            }
        }, "admin-dashboard-load");
        worker.setDaemon(true);
        worker.start();
    }

    private void render(AdminDashboardDto data) {
        totalMembersLabel.setText(String.valueOf(data.totalMembers));
        activeTodayLabel.setText(String.valueOf(data.activeToday));
        warnedLabel.setText(String.valueOf(data.warned));
        blacklistedLabel.setText(String.valueOf(data.blacklisted));

        // Group settings table
<<<<<<< HEAD
        groupSettingsTable.getItems().setAll(
                data.groupSettings == null ? java.util.List.of() : data.groupSettings);

        // Member table
        membersTable.getItems().setAll(
                data.members == null ? java.util.List.of() : data.members);
=======
        groupSettingsTable.setItems(FXCollections.observableArrayList(
                data.groupSettings == null ? java.util.List.of() : data.groupSettings));

        // Member table
        membersTable.setItems(FXCollections.observableArrayList(
                data.members == null ? java.util.List.of() : data.members));
>>>>>>> c0a0fe073da5b40940d7bd0bb2ce0c10d655d5ed

        if (statusLabel != null) {
            statusLabel.setManaged(false);
            statusLabel.setVisible(false);
        }
    }

    // ── Member row context menu ───────────────────────────────────────

    private TableRow<AdminMemberDto> memberRow() {
        TableRow<AdminMemberDto> row = new TableRow<>();
        ContextMenu menu = new ContextMenu();
        MenuItem blacklistItem = new MenuItem("Blacklist member…");
        MenuItem liftItem      = new MenuItem("Lift blacklist");
        blacklistItem.setOnAction(e -> promptBlacklist(row.getItem()));
        liftItem.setOnAction(e -> liftBlacklist(row.getItem()));
        menu.getItems().addAll(blacklistItem, liftItem);
        row.contextMenuProperty().bind(
                javafx.beans.binding.Bindings.when(row.emptyProperty())
                        .then((ContextMenu) null)
                        .otherwise(menu));
        return row;
    }

    private void promptBlacklist(AdminMemberDto member) {
        if (member == null) return;
        TextInputDialog reasonDlg = new TextInputDialog("Repeated policy violation");
        reasonDlg.setHeaderText("Blacklist " + member.username);
        reasonDlg.setContentText("Reason");
        java.util.Optional<String> reason = reasonDlg.showAndWait();
        if (reason.isEmpty() || reason.get().isBlank()) return;

        TextInputDialog daysDlg = new TextInputDialog("30");
        daysDlg.setHeaderText("Blacklist duration");
        daysDlg.setContentText("Days");
        java.util.Optional<String> daysText = daysDlg.showAndWait();
        if (daysText.isEmpty()) return;

        int days;
        try { days = Integer.parseInt(daysText.get().trim()); }
        catch (NumberFormatException e) { showStatus("Days must be a number."); return; }

        runMemberUpdate(() -> api.blacklistMember(Session.authToken(), member.userId, reason.get().trim(), days));
    }

    private void liftBlacklist(AdminMemberDto member) {
        if (member == null) return;
        runMemberUpdate(() -> api.liftBlacklist(Session.authToken(), member.userId));
    }

    private void runMemberUpdate(MemberUpdate update) {
        Thread worker = new Thread(() -> {
            try {
                update.run();
                AdminDashboardDto data = api.adminDashboard(Session.authToken());
                Platform.runLater(() -> render(data));
            } catch (Exception e) {
                if (e instanceof InterruptedException) Thread.currentThread().interrupt();
                Platform.runLater(() -> showStatus("Admin action failed: " + e.getMessage()));
            }
        }, "admin-member-update");
        worker.setDaemon(true);
        worker.start();
    }

    @FunctionalInterface
    private interface MemberUpdate {
        AdminMemberDto run() throws Exception;
    }

    // ── Nav handlers ─────────────────────────────────────────────────

    @FXML private void onDashboard()  { SceneManager.goAdminDashboard(); }
    @FXML private void onAnalytics()  { SceneManager.goAdminAnalytics(); }
    @FXML private void onMembers()    { SceneManager.goAdminMembers(); }
    @FXML private void onGroups()     { SceneManager.goGroups(); }

    @FXML private void onModeration()   { SceneManager.goAdminModeration(); }

    @FXML private void onProfile() { forum.app.SceneManager.goProfile(); }

    @FXML
    private void onLogout() {
        String token = Session.authToken();
        Session.end();
        new Thread(() -> new AuthService().logout(token), "logout").start();
        SceneManager.show("Login", "ACES");
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private String memberName(AdminMemberDto m) {
        return safe(m.username, "Unknown");
    }

    private String memberStatus(AdminMemberDto m) {
        if ("blacklisted".equalsIgnoreCase(m.status)) {
            return "Blacklisted";
        }
        if (m.unheededWarningCount > 0) {
            return "Warned";
        }
        // Capitalize first letter to match web ("Active" not "active")
        String s = safe(m.status, "Active");
        return s.substring(0, 1).toUpperCase() + s.substring(1).toLowerCase();
    }

    private String safe(String value, String fallback) {
        return value == null || value.isBlank() ? fallback : value;
    }

    /** Single first-letter initial — matches web x-avatar component. */
    private String initial(String name) {
        if (name == null || name.isBlank()) return "?";
        return String.valueOf(name.trim().charAt(0)).toUpperCase();
    }

    private void showStatus(String message) {
        if (statusLabel == null) return;
        statusLabel.setText(message);
        statusLabel.setManaged(true);
        statusLabel.setVisible(true);
    }
}

