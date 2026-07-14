package forum.controllers;

import forum.api.ApiClient;
import forum.api.ApiException;
import forum.api.dto.AdminMemberDto;
import forum.app.SceneManager;
import forum.app.Session;
import forum.models.User;
import forum.services.AuthService;
import forum.util.NavbarHelper;
import javafx.scene.control.MenuButton;

import javafx.application.Platform;
import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.geometry.Pos;
import javafx.scene.control.*;
import javafx.scene.layout.*;

import java.util.List;

/**
 * Controller for ComplianceMonitoring.fxml — the Members management page.
 * Mirrors web admin/members.blade.php:
 *   - Live search (username / email)
 *   - Filter tabs: All / Blacklisted / Warning / Active
 *   - Expandable member cards with warning history
 *   - Inline blacklist form (reason + days)
 *   - Lift blacklist button
 *   - Logout via nav-user chip
 */
public class AdminMembersController {

    @FXML private Label      avatarLabel;
    @FXML private Label      userNameLabel;
    @FXML private MenuButton notifButton;
    @FXML private Label      notifBadge;
    @FXML private TextField searchField;
    @FXML private ToggleGroup filterGroup;
    @FXML private ToggleButton btnAll;
    @FXML private ToggleButton btnBlacklisted;
    @FXML private ToggleButton btnWarning;
    @FXML private ToggleButton btnActive;
    @FXML private VBox     membersListBox;
    @FXML private Label    statusLabel;

    private final ApiClient api = new ApiClient();
    private String currentFilter = "all";

    @FXML
    private void initialize() {
        User user = Session.currentUser();
        if (user != null) {
            userNameLabel.setText(user.displayName());
            avatarLabel.setText(initial(user.displayName()));
        }
        NavbarHelper.loadNotifications(api, notifButton, notifBadge);

        // Wire filter toggles
        btnAll.setUserData("all");
        btnBlacklisted.setUserData("blacklisted");
        btnWarning.setUserData("warning");
        btnActive.setUserData("active");

        filterGroup.selectedToggleProperty().addListener((obs, old, neo) -> {
            if (neo == null) { old.setSelected(true); return; }
            currentFilter = (String) neo.getUserData();
            load();
        });

        load();
    }

    @FXML
    private void onSearch() {
        load();
    }

    private void load() {
        String token = Session.authToken();
        if (token == null || token.isBlank()) {
            showStatus("Members page requires an online session.");
            return;
        }
        String search = searchField.getText();
        String filter = currentFilter;

        membersListBox.getChildren().clear();
        Label loading = new Label("Loading members…");
        loading.getStyleClass().add("muted");
        membersListBox.getChildren().add(loading);

        Thread worker = new Thread(() -> {
            try {
                List<AdminMemberDto> members = api.adminMembers(token, filter, search);
                Platform.runLater(() -> renderMembers(members));
            } catch (ApiException | java.io.IOException | InterruptedException e) {
                if (e instanceof InterruptedException) Thread.currentThread().interrupt();
                Platform.runLater(() -> showStatus("Could not load members: " + e.getMessage()));
            }
        }, "admin-members-load");
        worker.setDaemon(true);
        worker.start();
    }

    // ---------------------------------------------------------------
    //  Rendering
    // ---------------------------------------------------------------

    private void renderMembers(List<AdminMemberDto> members) {
        membersListBox.getChildren().clear();
        if (members.isEmpty()) {
            Label empty = new Label("No members match this filter.");
            empty.getStyleClass().add("muted");
            empty.setPadding(new Insets(32));
            membersListBox.getChildren().add(empty);
            return;
        }
        for (AdminMemberDto m : members) {
            membersListBox.getChildren().add(buildMemberCard(m));
        }
        showStatus("Loaded " + members.size() + " member(s).");
    }

    /**
     * Builds a collapsible card for a single member — mirrors the Alpine x-data
     * expand/collapse in members.blade.php.
     */
    private VBox buildMemberCard(AdminMemberDto m) {
        // ── header row ──────────────────────────────────────────────
        Label avatar = new Label(initial(m.username));
        avatar.getStyleClass().addAll("avatar-soft", "avatar-lg");

        Label name = new Label(m.username == null ? "Unknown" : m.username);
        name.getStyleClass().add("label-strong");

        Label statusBadge = statusBadge(m);
        Label roleBadge   = new Label(formatRole(m.systemRole));
        roleBadge.getStyleClass().addAll("badge", "badge-neutral");

        HBox badges = new HBox(8, statusBadge, roleBadge);
        badges.setAlignment(Pos.CENTER_LEFT);

        String meta = (m.email == null ? "" : m.email)
                + " · " + m.postsCount + " posts"
                + " · last active " + (m.lastActiveHuman == null ? "never" : m.lastActiveHuman);
        Label metaLabel = new Label(meta);
        metaLabel.getStyleClass().add("subtle");
        metaLabel.setWrapText(true);

        VBox nameBox = new VBox(4, badges, name, metaLabel);

        Label chevron = new Label("⌄");
        chevron.getStyleClass().add("subtle");

        Region spacer = new Region();
        HBox.setHgrow(spacer, Priority.ALWAYS);

        HBox header = new HBox(14, avatar, nameBox, spacer, chevron);
        header.setAlignment(Pos.CENTER_LEFT);
        header.setStyle("-fx-cursor: hand;");

        // ── expandable body ──────────────────────────────────────────
        VBox body = new VBox(12);
        body.setManaged(false);
        body.setVisible(false);
        body.setPadding(new Insets(16, 0, 0, 0));

        Region divider = new Region();
        divider.getStyleClass().add("divider");
        divider.setPrefHeight(1);

        buildMemberCardBody(body, m);

        // ── card shell ───────────────────────────────────────────────
        VBox card = new VBox(0, header, divider, body);
        card.getStyleClass().add("card");
        VBox.setMargin(card, new Insets(0, 0, 12, 0));

        // toggle on header click
        header.setOnMouseClicked(e -> {
            boolean open = body.isVisible();
            body.setManaged(!open);
            body.setVisible(!open);
            chevron.setText(open ? "⌄" : "⌃");
        });

        return card;
    }

    private void buildMemberCardBody(VBox body, AdminMemberDto m) {
        // Access-restriction panel (blacklisted)
        if (m.activeBlacklist != null) {
            Label title = new Label("ACCESS RESTRICTION");
            title.getStyleClass().add("eyebrow");
            title.setStyle("-fx-text-fill:#b91c1c;");

            int days = m.activeBlacklist.daysRemaining == null ? 0 : m.activeBlacklist.daysRemaining;
            Label days_ = new Label(days + " days remaining");
            days_.getStyleClass().add("alert-danger-text");

            String reason = m.activeBlacklist.reason == null ? "" : m.activeBlacklist.reason;
            Label reasonLbl = new Label("Reason: " + reason);
            reasonLbl.setStyle("-fx-text-fill:#dc2626; -fx-font-size:12px;");

            VBox info = new VBox(2, title, days_, reasonLbl);

            Button liftBtn = new Button("Lift Blacklist");
            liftBtn.getStyleClass().addAll("btn", "btn-outline");
            liftBtn.setStyle("-fx-text-fill:#b91c1c; -fx-border-color:#fecaca;");
            liftBtn.setOnAction(e -> liftBlacklist(m));

            Region sp = new Region();
            HBox.setHgrow(sp, Priority.ALWAYS);

            HBox alertBox = new HBox(sp, info, new Region(), liftBtn);
            alertBox.getStyleClass().add("alert-danger");
            alertBox.setAlignment(Pos.CENTER_LEFT);
            body.getChildren().add(alertBox);

        } else if ("blacklisted".equalsIgnoreCase(m.status)) {
            // Blacklisted but no active record found
            Label noRecord = new Label("Blacklisted — no active restriction record found.");
            noRecord.setStyle("-fx-text-fill:#dc2626; -fx-font-size:12px;");
            Button liftBtn = new Button("Lift Blacklist");
            liftBtn.getStyleClass().addAll("btn", "btn-outline");
            liftBtn.setStyle("-fx-text-fill:#b91c1c; -fx-border-color:#fecaca;");
            liftBtn.setOnAction(e -> liftBlacklist(m));
            HBox row = new HBox(12, noRecord, new Region(), liftBtn);
            HBox.setHgrow(row.getChildren().get(1), Priority.ALWAYS);
            row.setAlignment(Pos.CENTER_LEFT);
            row.getStyleClass().add("alert-danger");
            body.getChildren().add(row);
        }

        // Warning history
        if (m.unheededWarningCount > 0) {
            Label historyTitle = new Label("WARNING HISTORY");
            historyTitle.getStyleClass().add("eyebrow");
            VBox historyBox = new VBox(4, historyTitle);
            int num = m.latestWarningNumber == null ? m.unheededWarningCount : m.latestWarningNumber;
            for (int i = 1; i <= num; i++) {
                Label warnRow = new Label("Warning #" + i + " — unheeded");
                warnRow.getStyleClass().add("subtle");
                historyBox.getChildren().add(warnRow);
            }
            body.getChildren().add(historyBox);
        }

        // Blacklist form (only if not already blacklisted and not system_admin)
        if (!"blacklisted".equalsIgnoreCase(m.status) && !"system_admin".equalsIgnoreCase(m.systemRole)) {
            Label formTitle = new Label("Blacklist this member");
            formTitle.getStyleClass().add("eyebrow");

            TextField reasonField = new TextField();
            reasonField.setPromptText("e.g. Repeated irrelevant posting");
            HBox.setHgrow(reasonField, Priority.ALWAYS);

            TextField daysField = new TextField("30");
            daysField.setPrefWidth(70);

            Label reasonLbl = new Label("Reason");
            reasonLbl.getStyleClass().add("field-label");
            Label daysLbl = new Label("Days");
            daysLbl.getStyleClass().add("field-label");

            VBox reasonCol = new VBox(4, reasonLbl, reasonField);
            HBox.setHgrow(reasonCol, Priority.ALWAYS);
            VBox daysCol   = new VBox(4, daysLbl, daysField);

            Button blacklistBtn = new Button("Blacklist");
            blacklistBtn.getStyleClass().addAll("btn", "btn-danger");
            blacklistBtn.setAlignment(Pos.BOTTOM_LEFT);

            HBox form = new HBox(12, reasonCol, daysCol, blacklistBtn);
            form.setAlignment(Pos.BOTTOM_LEFT);
            form.setStyle("-fx-background-color:#f9fafb; -fx-background-radius:8; -fx-padding:16;");

            blacklistBtn.setOnAction(e -> {
                String reason = reasonField.getText().trim();
                String daysText = daysField.getText().trim();
                if (reason.isEmpty()) { showStatus("Please enter a reason."); return; }
                int days;
                try { days = Integer.parseInt(daysText); } catch (NumberFormatException ex) {
                    showStatus("Days must be a number.");
                    return;
                }
                blacklistMember(m, reason, days);
            });

            VBox formSection = new VBox(8, formTitle, form);
            body.getChildren().add(formSection);
        }
    }

    // ---------------------------------------------------------------
    //  API actions
    // ---------------------------------------------------------------

    private void blacklistMember(AdminMemberDto member, String reason, int days) {
        String token = Session.authToken();
        if (token == null) return;
        Thread t = new Thread(() -> {
            try {
                api.blacklistMember(token, member.userId, reason, days);
                Platform.runLater(() -> { showStatus(member.username + " has been blacklisted."); load(); });
            } catch (Exception e) {
                if (e instanceof InterruptedException) Thread.currentThread().interrupt();
                Platform.runLater(() -> showStatus("Blacklist failed: " + e.getMessage()));
            }
        }, "admin-blacklist");
        t.setDaemon(true);
        t.start();
    }

    private void liftBlacklist(AdminMemberDto member) {
        String token = Session.authToken();
        if (token == null) return;
        Thread t = new Thread(() -> {
            try {
                api.liftBlacklist(token, member.userId);
                Platform.runLater(() -> { showStatus(member.username + "'s blacklist lifted."); load(); });
            } catch (Exception e) {
                if (e instanceof InterruptedException) Thread.currentThread().interrupt();
                Platform.runLater(() -> showStatus("Lift failed: " + e.getMessage()));
            }
        }, "admin-lift");
        t.setDaemon(true);
        t.start();
    }

    // ---------------------------------------------------------------
    //  Nav handlers
    // ---------------------------------------------------------------

    @FXML private void onDashboard() { SceneManager.goAdminDashboard(); }
    @FXML private void onGroups()    { SceneManager.goGroups(); }
    @FXML private void onMembers()   { /* already here */ }
    @FXML private void onAnalytics() { SceneManager.goAdminAnalytics(); }

    @FXML private void onProfile() { forum.app.SceneManager.goProfile(); }

    @FXML
    private void onLogout() {
        String token = Session.authToken();
        Session.end();
        new Thread(() -> new AuthService().logout(token), "logout").start();
        SceneManager.show("Login", "Smart Discussion Forum");
    }

    // ---------------------------------------------------------------
    //  Helpers
    // ---------------------------------------------------------------

    private Label statusBadge(AdminMemberDto m) {
        Label badge = new Label();
        badge.getStyleClass().add("badge");
        if ("blacklisted".equalsIgnoreCase(m.status)) {
            badge.setText("⛔ Blacklisted");
            badge.getStyleClass().add("badge-danger");
        } else if (m.unheededWarningCount > 0) {
            int num = m.latestWarningNumber == null ? m.unheededWarningCount : m.latestWarningNumber;
            badge.setText("⚠ Warning #" + num);
            badge.getStyleClass().add("badge-warning");
        } else {
            badge.setText("Active");
            badge.getStyleClass().add("badge-success");
        }
        return badge;
    }

    private String formatRole(String role) {
        if (role == null) return "User";
        return switch (role) {
            case "system_admin" -> "Admin";
            case "lecturer"     -> "Lecturer";
            default             -> "Student";
        };
    }

    private String initial(String name) {
        if (name == null || name.isBlank()) return "?";
        return String.valueOf(name.trim().charAt(0)).toUpperCase();
    }

    private void showStatus(String msg) {
        if (statusLabel == null) return;
        statusLabel.setText(msg);
        statusLabel.setManaged(true);
        statusLabel.setVisible(true);
    }
}
