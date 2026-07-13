package forum.controllers;

import forum.api.ApiClient;
import forum.api.dto.AdminDashboardDto;
import forum.api.dto.AdminMemberDto;
import forum.app.SceneManager;
import forum.app.Session;
import forum.models.User;
import forum.services.AuthService;

import javafx.application.Platform;
import javafx.beans.property.SimpleStringProperty;
import javafx.collections.FXCollections;
import javafx.fxml.FXML;
import javafx.scene.control.*;

public class AdminDashboardController {

    @FXML private Label  avatarLabel;
    @FXML private Label  userNameLabel;
    @FXML private Label  totalMembersLabel;
    @FXML private Label  activeTodayLabel;
    @FXML private Label  warnedLabel;
    @FXML private Label  blacklistedLabel;
    @FXML private Label  statusLabel;

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
            avatarLabel.setText(initials(user.displayName()));
        }

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
        groupSettingsTable.setItems(FXCollections.observableArrayList(
                data.groupSettings == null ? java.util.List.of() : data.groupSettings));

        // Member table
        membersTable.setItems(FXCollections.observableArrayList(
                data.members == null ? java.util.List.of() : data.members));

        showStatus("Loaded from web backend.");
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

    @FXML private void onProfile() { forum.app.SceneManager.goProfile(); }

    @FXML
    private void onLogout() {
        String token = Session.authToken();
        Session.end();
        new Thread(() -> new AuthService().logout(token), "logout").start();
        SceneManager.show("Login", "Smart Discussion Forum");
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private String memberName(AdminMemberDto m) {
        return safe(m.username, "Unknown") + " <" + safe(m.email, "no email") + ">";
    }

    private String memberStatus(AdminMemberDto m) {
        if ("blacklisted".equalsIgnoreCase(m.status)) {
            String days = m.activeBlacklist != null && m.activeBlacklist.daysRemaining != null
                    ? " (" + m.activeBlacklist.daysRemaining + " days left)" : "";
            return "Blacklisted" + days;
        }
        if (m.unheededWarningCount > 0) {
            return "Warning #" + (m.latestWarningNumber == null
                    ? m.unheededWarningCount : m.latestWarningNumber);
        }
        return safe(m.status, "Active");
    }

    private String safe(String value, String fallback) {
        return value == null || value.isBlank() ? fallback : value;
    }

    private String initials(String name) {
        if (name == null || name.isBlank()) return "??";
        String t = name.trim();
        return (t.length() >= 2 ? t.substring(0, 2) : t).toUpperCase();
    }

    private void showStatus(String message) {
        if (statusLabel == null) return;
        statusLabel.setText(message);
        statusLabel.setManaged(true);
        statusLabel.setVisible(true);
    }
}
