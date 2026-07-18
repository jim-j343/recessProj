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

        // Auto-resize tables (Fixed height to match web style)
        reportsTable.getStyleClass().add("moderation-table");
        reportsTable.setFixedCellSize(72); // larger size for 2-line post column
        reportsTable.prefHeightProperty().bind(
            javafx.beans.binding.Bindings.max(1, javafx.beans.binding.Bindings.size(reportsTable.getItems()))
                .multiply(reportsTable.getFixedCellSize()).add(36)
        );

        removalsTable.getStyleClass().add("moderation-table");
        removalsTable.setFixedCellSize(72);
        removalsTable.prefHeightProperty().bind(
            javafx.beans.binding.Bindings.max(1, javafx.beans.binding.Bindings.size(removalsTable.getItems()))
                .multiply(removalsTable.getFixedCellSize()).add(36)
        );

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
        colReportPost.setCellFactory(tc -> new TableCell<AdminReportDto, String>() {
            @Override protected void updateItem(String item, boolean empty) {
                super.updateItem(item, empty);
                if (empty || getTableRow() == null || getTableRow().getItem() == null) {
                    setGraphic(null); setText(null); return;
                }
                AdminReportDto r = getTableRow().getItem();
                VBox box = new VBox(4);
                box.setAlignment(javafx.geometry.Pos.CENTER_LEFT);
                
                Label title = new Label(r.topicTitle != null ? r.topicTitle : "—");
                title.setStyle("-fx-text-fill: #4f46e5; -fx-font-size: 14px; -fx-font-weight: 500; -fx-cursor: hand;");
                title.setOnMouseClicked(e -> {
                    if (r.topicId != null) {
                        forum.models.Topic t = new forum.models.Topic();
                        t.setTopicId(r.topicId);
                        t.setTitle(r.topicTitle != null ? r.topicTitle : "Topic");
                        forum.app.ViewState.setSelectedTopic(t);
                        forum.app.SceneManager.show("TopicDetail", "ACES — " + t.getTitle());
                    }
                });
                
                String contentStr = r.postContent != null
                        ? (r.postContent.length() > 60 ? r.postContent.substring(0, 60) + "…" : r.postContent)
                        : "";
                Label content = new Label(contentStr);
                content.setStyle("-fx-text-fill: #9ca3af; -fx-font-size: 13px;");
                
                box.getChildren().addAll(title, content);
                setGraphic(box);
                setText(null);
            }
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
                HBox box = new HBox(16);
                box.setAlignment(javafx.geometry.Pos.CENTER_LEFT);
                if (row.reviewed) {
                    Label badge = new Label("Reviewed" + (row.reviewedBy != null ? " by " + row.reviewedBy : ""));
                    badge.setStyle("-fx-background-color: #dcfce7; -fx-text-fill: #15803d; -fx-padding: 4 10 4 10; -fx-background-radius: 999; -fx-font-size: 11.5px; -fx-font-weight: 600;");
                    box.getChildren().add(badge);
                } else {
                    Label badge = new Label("Needs review");
                    badge.setStyle("-fx-background-color: #fef9c3; -fx-text-fill: #a16207; -fx-padding: 4 10 4 10; -fx-background-radius: 999; -fx-font-size: 11.5px; -fx-font-weight: 600;");
                    
                    Label btn = new Label("Mark\nReviewed"); // Two lines like the web
                    btn.setStyle("-fx-text-fill: #4f46e5; -fx-font-weight: 600; -fx-cursor: hand; -fx-font-size: 12px; -fx-alignment: center-left;");
                    btn.setOnMouseClicked(e -> markReportReviewed(row.id));
                    
                    box.getChildren().addAll(badge, btn);
                }
                setGraphic(box);
            }
        });

        reportsTable.setRowFactory(table -> {
            TableRow<AdminReportDto> row = new TableRow<>();
            ContextMenu menu = new ContextMenu();
            MenuItem reviewItem = new MenuItem("Mark as Reviewed");
            reviewItem.setOnAction(e -> markReportReviewed(row.getItem().id));
            menu.getItems().add(reviewItem);
            row.contextMenuProperty().bind(
                    javafx.beans.binding.Bindings.when(row.emptyProperty())
                            .then((ContextMenu) null)
                            .otherwise(menu));
            return row;
        });
    }

    private void initRemovalsTable() {
        colMember.setCellValueFactory(data -> new SimpleStringProperty(data.getValue().removedUser));
        colMember.setCellFactory(tc -> new TableCell<AdminRemovalDto, String>() {
            @Override protected void updateItem(String item, boolean empty) {
                super.updateItem(item, empty);
                if (empty || item == null) { setGraphic(null); setText(null); return; }
                Label lbl = new Label(item);
                lbl.setStyle("-fx-font-weight: 800; -fx-font-size: 13px; -fx-text-fill: #111827;");
                setGraphic(lbl);
                setText(null);
            }
        });

        colGroup.setCellValueFactory(data -> new SimpleStringProperty(data.getValue().groupName));
        colGroup.setCellFactory(tc -> new TableCell<AdminRemovalDto, String>() {
            @Override protected void updateItem(String item, boolean empty) {
                super.updateItem(item, empty);
                if (empty || item == null) { setGraphic(null); setText(null); return; }
                Label lbl = new Label(item);
                lbl.setWrapText(true);
                lbl.setStyle("-fx-text-fill: #6b7280; -fx-font-size: 13px;");
                setGraphic(lbl);
                setText(null);
            }
        });

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
                HBox box = new HBox(16); // spacing between badge and button
                box.setAlignment(javafx.geometry.Pos.CENTER_LEFT);
                if (row.reviewed) {
                    Label badge = new Label("Reviewed" + (row.reviewedBy != null ? " by " + row.reviewedBy : ""));
                    badge.setStyle("-fx-background-color: #dcfce7; -fx-text-fill: #15803d; -fx-padding: 4 10 4 10; -fx-background-radius: 999; -fx-font-size: 11.5px; -fx-font-weight: 600;");
                    box.getChildren().add(badge);
                } else {
                    Label badge = new Label("Needs review");
                    badge.setStyle("-fx-background-color: #fef9c3; -fx-text-fill: #a16207; -fx-padding: 4 10 4 10; -fx-background-radius: 999; -fx-font-size: 11.5px; -fx-font-weight: 600;");
                    
                    Label btn = new Label("Mark\nReviewed"); // Two lines like the web
                    btn.setStyle("-fx-text-fill: #4f46e5; -fx-font-weight: 600; -fx-cursor: hand; -fx-font-size: 12px; -fx-alignment: center-left;");
                    btn.setOnMouseClicked(e -> markRemovalReviewed(row.id));
                    
                    box.getChildren().addAll(badge, btn);
                }
                setGraphic(box);
            }
        });

        removalsTable.setRowFactory(table -> {
            TableRow<AdminRemovalDto> row = new TableRow<>();
            ContextMenu menu = new ContextMenu();
            MenuItem reviewItem = new MenuItem("Mark as Reviewed");
            reviewItem.setOnAction(e -> markRemovalReviewed(row.getItem().id));
            menu.getItems().add(reviewItem);
            row.contextMenuProperty().bind(
                    javafx.beans.binding.Bindings.when(row.emptyProperty())
                            .then((ContextMenu) null)
                            .otherwise(menu));
            return row;
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
        SceneManager.show("Login", "ACES");
    }

    private String initial(String name) {
        if (name == null || name.isBlank()) return "?";
        return String.valueOf(name.trim().charAt(0)).toUpperCase();
    }
}
