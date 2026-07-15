package forum.controllers;

import forum.api.ApiClient;
import forum.api.ApiException;
import forum.api.dto.AdminRemovalDto;
import forum.api.dto.AdminRemovalsResponseDto;
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
import java.io.IOException;
import java.util.List;

public class AdminRemovalsController {
    
    @FXML private Label statusLabel;
    @FXML private ToggleButton btnNeedsReview;
    @FXML private ToggleButton btnReviewed;
    @FXML private ToggleButton btnAll;
    
    @FXML private TableView<AdminRemovalDto> removalsTable;
    @FXML private TableColumn<AdminRemovalDto, String> colMember;
    @FXML private TableColumn<AdminRemovalDto, String> colGroup;
    @FXML private TableColumn<AdminRemovalDto, String> colRemovedBy;
    @FXML private TableColumn<AdminRemovalDto, String> colReason;
    @FXML private TableColumn<AdminRemovalDto, String> colWhen;
    @FXML private TableColumn<AdminRemovalDto, String> colStatus;
    
    @FXML private Label avatarLabel;
    @FXML private Label userNameLabel;
    @FXML private MenuButton notifButton;
    @FXML private Label notifBadge;
    
    private final ApiClient api = new ApiClient();
    private final ToggleGroup filterGroup = new ToggleGroup();
    
    @FXML
    private void initialize() {
        User user = Session.currentUser();
        if (user != null) {
            userNameLabel.setText(user.displayName());
            avatarLabel.setText(initial(user.displayName()));
        }
        NavbarHelper.loadNotifications(api, notifButton, notifBadge);
        
        btnNeedsReview.setToggleGroup(filterGroup);
        btnReviewed.setToggleGroup(filterGroup);
        btnAll.setToggleGroup(filterGroup);
        
        colMember.setCellValueFactory(data -> new SimpleStringProperty(data.getValue().removedUser));
        colGroup.setCellValueFactory(data -> new SimpleStringProperty(data.getValue().groupName));
        colRemovedBy.setCellValueFactory(data -> new SimpleStringProperty(data.getValue().removedBy));
        colReason.setCellValueFactory(data -> new SimpleStringProperty(data.getValue().reason));
        colWhen.setCellValueFactory(data -> new SimpleStringProperty(data.getValue().createdAtHuman));
        
        colStatus.setCellFactory(tc -> new TableCell<AdminRemovalDto, String>() {
            @Override
            protected void updateItem(String item, boolean empty) {
                super.updateItem(item, empty);
                if (empty || getTableRow() == null || getTableRow().getItem() == null) {
                    setGraphic(null);
                } else {
                    AdminRemovalDto row = getTableRow().getItem();
                    HBox box = new HBox(6);
                    if (row.reviewed) {
                        Label lbl = new Label("Reviewed");
                        lbl.getStyleClass().addAll("badge", "badge-success");
                        box.getChildren().add(lbl);
                    } else {
                        Button reviewBtn = new Button("Mark Reviewed");
                        reviewBtn.getStyleClass().addAll("btn-sm", "btn-primary");
                        reviewBtn.setOnAction(e -> markReviewed(row.id));
                        box.getChildren().add(reviewBtn);
                    }
                    setGraphic(box);
                }
            }
        });
        
        filterGroup.selectedToggleProperty().addListener((obs, oldVal, newVal) -> {
            if (newVal == null) {
                oldVal.setSelected(true);
                return;
            }
            loadData();
        });
        
        loadData();
    }
    
    private void loadData() {
        if (!Session.isAuthenticated()) return;
        String filter = "unreviewed";
        if (btnReviewed.isSelected()) filter = "reviewed";
        else if (btnAll.isSelected()) filter = "all";
        
        String finalFilter = filter;
        new Thread(() -> {
            try {
                AdminRemovalsResponseDto resp = api.getRemovals(Session.authToken(), finalFilter);
                Platform.runLater(() -> {
                    removalsTable.getItems().setAll(resp.removals);
                    statusLabel.setManaged(false);
                    statusLabel.setVisible(false);
                });
            } catch (ApiException | IOException | InterruptedException e) {
                Platform.runLater(() -> {
                    statusLabel.setText(e.getMessage());
                    statusLabel.setManaged(true);
                    statusLabel.setVisible(true);
                });
            }
        }).start();
    }
    
    private void markReviewed(long id) {
        new Thread(() -> {
            try {
                api.markRemovalReviewed(Session.authToken(), id);
                Platform.runLater(this::loadData);
            } catch (Exception e) {
                Platform.runLater(() -> {
                    statusLabel.setText(e.getMessage());
                    statusLabel.setManaged(true);
                    statusLabel.setVisible(true);
                });
            }
        }).start();
    }
    
    @FXML private void onDashboard() { SceneManager.goAdminDashboard(); }
    @FXML private void onGroups() { SceneManager.goGroups(); }
    @FXML private void onMembers() { SceneManager.goAdminMembers(); }
    @FXML private void onAnalytics() { SceneManager.goAdminAnalytics(); }
    
    @FXML private void onProfile() { SceneManager.show("ProfileEdit", "Profile"); }
    
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
