package forum.controllers;

import forum.api.ApiClient;
import forum.api.ApiException;
import forum.api.dto.AdminRemovalDto;
import forum.api.dto.AdminRemovalsResponseDto;
import forum.api.dto.AdminReportDto;
import forum.api.dto.AdminReportsResponseDto;
import forum.app.SceneManager;
import forum.app.Session;
import forum.models.User;
import forum.services.AuthService;
import forum.util.NavbarHelper;
import javafx.application.Platform;
import javafx.beans.property.SimpleStringProperty;
import javafx.fxml.FXML;
import javafx.scene.control.*;
import javafx.scene.layout.HBox;
import javafx.scene.layout.VBox;
import java.io.IOException;

public class AdminModerationController {

    // ── Navbar ──────────────────────────────────────────────────────
    @FXML private Label avatarLabel;
    @FXML private Label userNameLabel;
    @FXML private MenuButton notifButton;
    @FXML private Label notifBadge;

    // ── Section tab toggles (underline style) ────────────────────────
    @FXML private ToggleButton btnTabReports;
    @FXML private ToggleButton btnTabRemovals;

    // ── Filter toggles ───────────────────────────────────────────────
    @FXML private ToggleButton btnNeedsReview;
    @FXML private ToggleButton btnReviewed;
    @FXML private ToggleButton btnAll;

    // ── Panes (swapped on tab switch) ────────────────────────────────
    @FXML private VBox reportsPane;
    @FXML private VBox removalsPane;

    // ── Status label ─────────────────────────────────────────────────
    @FXML private Label statusLabel;

    // ── Post Reports table ────────────────────────────────────────────
    @FXML private TableView<AdminReportDto> reportsTable;
    @FXML private TableColumn<AdminReportDto, String> colReportPost;
    @FXML private TableColumn<AdminReportDto, String> colReportAuthor;
    @FXML private TableColumn<AdminReportDto, String> colReportedBy;
    @FXML private TableColumn<AdminReportDto, String> colReportReason;
    @FXML private TableColumn<AdminReportDto, String> colReportWhen;
    @FXML private TableColumn<AdminReportDto, String> colReportStatus;

    // ── Member Removals table ─────────────────────────────────────────
    @FXML private TableView<AdminRemovalDto> removalsTable;
    @FXML private TableColumn<AdminRemovalDto, String> colMember;
    @FXML private TableColumn<AdminRemovalDto, String> colGroup;
    @FXML private TableColumn<AdminRemovalDto, String> colRemovedBy;
    @FXML private TableColumn<AdminRemovalDto, String> colReason;
    @FXML private TableColumn<AdminRemovalDto, String> colWhen;
    @FXML private TableColumn<AdminRemovalDto, String> colStatus;

    private final ApiClient api = new ApiClient();
    private final ToggleGroup tabGroup    = new ToggleGroup();
    private final ToggleGroup filterGroup = new ToggleGroup();

    @FXML
    private void initialize() {
        // Navbar user info
        User user = Session.currentUser();
        if (user != null) {
            userNameLabel.setText(user.displayName());
            avatarLabel.setText(initial(user.displayName()));
        }
        NavbarHelper.loadNotifications(api, notifButton, notifBadge);

        // Wire tab toggles (mutually exclusive)
        btnTabReports.setToggleGroup(tabGroup);
        btnTabRemovals.setToggleGroup(tabGroup);

        // Wire filter toggles
        btnNeedsReview.setToggleGroup(filterGroup);
        btnReviewed.setToggleGroup(filterGroup);
        btnAll.setToggleGroup(filterGroup);

        // Set up column cell factories
        initReportsTable();
        initRemovalsTable();

        // Tab switch → swap visible pane + reload
        tabGroup.selectedToggleProperty().addListener((obs, old, newVal) -> {
            if (newVal == null) { old.setSelected(true); return; }
            boolean reports = (newVal == btnTabReports);
            reportsPane.setVisible(reports);
            reportsPane.setManaged(reports);
            removalsPane.setVisible(!reports);
            removalsPane.setManaged(!reports);
            loadData();
        });

        // Filter change → reload same tab
        filterGroup.selectedToggleProperty().addListener((obs, old, newVal) -> {
            if (newVal == null) { old.setSelected(true); return; }
            loadData();
        });

        loadData();
    }

    // ── Table setup ──────────────────────────────────────────────────

    private void initReportsTable() {
        // "Post" column: shows topic title + truncated content (like web)
        colReportPost.setCellValueFactory(data -> {
            AdminReportDto r = data.getValue();
            String title = r.topicTitle != null ? r.topicTitle : "—";
            String content = r.postContent != null
                    ? (r.postContent.length() > 60 ? r.postContent.substring(0, 60) + "…" : r.postContent)
                    : "";
            return new SimpleStringProperty(title + (content.isEmpty() ? "" : "\n" + content));
        });
        colReportAuthor.setCellValueFactory(data -> new SimpleStringProperty(data.getValue().author));
        colReportedBy.setCellValueFactory(data -> new SimpleStringProperty(data.getValue().reportedBy));
        colReportReason.setCellValueFactory(data -> new SimpleStringProperty(data.getValue().reason));
        colReportWhen.setCellValueFactory(data -> new SimpleStringProperty(data.getValue().createdAtHuman));

        colReportStatus.setCellFactory(tc -> new TableCell<AdminReportDto, String>() {
            @Override protected void updateItem(String item, boolean empty) {
                super.updateItem(item, empty);
                if (empty || getTableRow() == null || getTableRow().getItem() == null) {
                    setGraphic(null); return;
                }
                AdminReportDto row = getTableRow().getItem();
                HBox box = new HBox(8);
                box.setAlignment(javafx.geometry.Pos.CENTER_LEFT);
                if (row.reviewed) {
                    Label badge = new Label("Reviewed" + (row.reviewedBy != null ? " by " + row.reviewedBy : ""));
                    badge.setStyle("-fx-background-color: #dcfce7; -fx-text-fill: #15803d; -fx-padding: 3 8 3 8; -fx-background-radius: 999; -fx-font-size: 11px; -fx-font-weight: 600;");
                    box.getChildren().add(badge);
                } else {
                    Label badge = new Label("Needs review");
                    badge.setStyle("-fx-background-color: #fef9c3; -fx-text-fill: #a16207; -fx-padding: 3 8 3 8; -fx-background-radius: 999; -fx-font-size: 11px; -fx-font-weight: 600;");
                    Button btn = new Button("Mark Reviewed");
                    btn.getStyleClass().addAll("btn-sm", "btn-primary");
                    btn.setOnAction(e -> markReportReviewed(row.id));
                    box.getChildren().addAll(badge, btn);
                }
                setGraphic(box);
            }
        });
    }

    private void initRemovalsTable() {
        colMember.setCellValueFactory(data -> new SimpleStringProperty(data.getValue().removedUser));
        colGroup.setCellValueFactory(data -> new SimpleStringProperty(data.getValue().groupName));
        colRemovedBy.setCellValueFactory(data -> new SimpleStringProperty(data.getValue().removedBy));
        colReason.setCellValueFactory(data -> new SimpleStringProperty(data.getValue().reason));
        colWhen.setCellValueFactory(data -> new SimpleStringProperty(data.getValue().createdAtHuman));

        colStatus.setCellFactory(tc -> new TableCell<AdminRemovalDto, String>() {
            @Override protected void updateItem(String item, boolean empty) {
                super.updateItem(item, empty);
                if (empty || getTableRow() == null || getTableRow().getItem() == null) {
                    setGraphic(null); return;
                }
                AdminRemovalDto row = getTableRow().getItem();
                HBox box = new HBox(8);
                box.setAlignment(javafx.geometry.Pos.CENTER_LEFT);
                if (row.reviewed) {
                    Label badge = new Label("Reviewed" + (row.reviewedBy != null ? " by " + row.reviewedBy : ""));
                    badge.setStyle("-fx-background-color: #dcfce7; -fx-text-fill: #15803d; -fx-padding: 3 8 3 8; -fx-background-radius: 999; -fx-font-size: 11px; -fx-font-weight: 600;");
                    box.getChildren().add(badge);
                } else {
                    Label badge = new Label("Needs review");
                    badge.setStyle("-fx-background-color: #fef9c3; -fx-text-fill: #a16207; -fx-padding: 3 8 3 8; -fx-background-radius: 999; -fx-font-size: 11px; -fx-font-weight: 600;");
                    Button btn = new Button("Mark Reviewed");
                    btn.getStyleClass().addAll("btn-sm", "btn-primary");
                    btn.setOnAction(e -> markRemovalReviewed(row.id));
                    box.getChildren().addAll(badge, btn);
                }
                setGraphic(box);
            }
        });
    }

    // ── Data loading ─────────────────────────────────────────────────

    private void loadData() {
        if (!Session.isAuthenticated()) return;
        String filter = "unreviewed";
        if (btnReviewed.isSelected()) filter = "reviewed";
        else if (btnAll.isSelected()) filter = "all";
        final String f = filter;

        boolean isReportsTab = btnTabReports.isSelected();
        new Thread(() -> {
            try {
                if (isReportsTab) {
                    AdminReportsResponseDto resp = api.getReports(Session.authToken(), f);
                    final var items = resp.reports;
                    Platform.runLater(() -> {
                        reportsTable.getItems().setAll(items);
                        hideStatus();
                    });
                } else {
                    AdminRemovalsResponseDto resp = api.getRemovals(Session.authToken(), f);
                    final var items = resp.removals;
                    Platform.runLater(() -> {
                        removalsTable.getItems().setAll(items);
                        hideStatus();
                    });
                }
            } catch (ApiException | IOException | InterruptedException e) {
                showStatus(e.getMessage());
            }
        }).start();
    }

    private void markReportReviewed(long id) {
        new Thread(() -> {
            try {
                api.markReportReviewed(Session.authToken(), id);
                Platform.runLater(this::loadData);
            } catch (Exception e) { showStatus(e.getMessage()); }
        }).start();
    }

    private void markRemovalReviewed(long id) {
        new Thread(() -> {
            try {
                api.markRemovalReviewed(Session.authToken(), id);
                Platform.runLater(this::loadData);
            } catch (Exception e) { showStatus(e.getMessage()); }
        }).start();
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private void showStatus(String msg) {
        Platform.runLater(() -> {
            statusLabel.setText(msg);
            statusLabel.setManaged(true);
            statusLabel.setVisible(true);
        });
    }
    private void hideStatus() {
        statusLabel.setManaged(false);
        statusLabel.setVisible(false);
    }

    // ── Navigation ───────────────────────────────────────────────────
    @FXML private void onDashboard() { SceneManager.goAdminDashboard(); }
    @FXML private void onGroups()    { SceneManager.goGroups(); }
    @FXML private void onMembers()   { SceneManager.goAdminMembers(); }
    @FXML private void onAnalytics() { SceneManager.goAdminAnalytics(); }
    @FXML private void onProfile()   { SceneManager.show("ProfileEdit", "Profile"); }
    @FXML private void onLogout() {
        String token = Session.authToken();
        Session.end();
        new Thread(() -> new AuthService().logout(token), "logout").start();
        SceneManager.show("Login", "Smart Discussion Forum");
    }

    private String initial(String name) {
        if (name == null || name.isBlank()) return "?";
        return String.valueOf(name.trim().charAt(0)).toUpperCase();
    }
}
