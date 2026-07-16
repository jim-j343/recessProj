package forum.app;

import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.stage.Stage;

import java.io.IOException;

public final class SceneManager {

    private static Stage stage;
    private SceneManager() {}

    public static void init(Stage primaryStage) { stage = primaryStage; }

    public static void show(String fxmlName) { show(fxmlName, "ACES"); }

    public static void show(String fxmlName, String title) {
        if (stage == null) throw new IllegalStateException("SceneManager not initialised");
        try {
            FXMLLoader loader = new FXMLLoader(
                    SceneManager.class.getResource("/forum/fxml/" + fxmlName + ".fxml"));
            Parent root = loader.load();
            Scene existing = stage.getScene();
            if (existing == null) {
                stage.setScene(new Scene(root));
            } else {
                existing.setRoot(root);
            }
            stage.setTitle(title);
            stage.sizeToScene();
            stage.centerOnScreen();
            stage.show();
        } catch (IOException e) {
            e.printStackTrace();
        }
    }

    public static <T> T showAndGetController(String fxmlName, String title) {
        if (stage == null) throw new IllegalStateException("SceneManager not initialised");
        try {
            FXMLLoader loader = new FXMLLoader(
                    SceneManager.class.getResource("/forum/fxml/" + fxmlName + ".fxml"));
            Parent root = loader.load();
            Scene existing = stage.getScene();
            if (existing == null) {
                stage.setScene(new Scene(root));
            } else {
                existing.setRoot(root);
            }
            stage.setTitle(title);
            stage.sizeToScene();
            stage.centerOnScreen();
            stage.show();
            return loader.getController();
        } catch (IOException e) {
            e.printStackTrace();
            return null;
        }
    }

    public static void showHomeFor(forum.models.Role role) {
        switch (role) {
            case LECTURER     -> show("LecturerDashboard", "ACES — Lecturer");
            case SYSTEM_ADMIN -> show("AdminDashboard",    "ACES — Admin");
            default           -> show("StudentDashboard",  "ACES — Student");
        }
    }

    // Named nav helpers keep controllers clean
    public static void goStudentDashboard()   { show("StudentDashboard",    "ACES — Student"); }
    public static void goLecturerDashboard()  { show("LecturerDashboard",   "ACES — Lecturer"); }
    public static void goGroups() {
        if (Session.currentUser() != null && Session.currentUser().getRole() == forum.models.Role.SYSTEM_ADMIN) {
            show("AdminGroupsIndex", "ACES — Admin Groups");
        } else {
            show("GroupsIndex", "ACES — Groups");
        }
    }
    public static void goGroupShow()          { show("GroupShow",            "ACES — Group"); }
    public static void showGroup(long groupId) {
        // Find group and set ViewState, or assume ViewState is already set
        show("GroupShow", "ACES — Group");
    }
    public static void goGroupEdit(forum.api.dto.GroupDto group) {
        forum.controllers.GroupEditController controller = showAndGetController("GroupEdit", "ACES — Edit Group");
        if (controller != null) controller.setGroup(group);
    }
    public static void goNotifications()      { show("NotificationsIndex",   "ACES — Notifications"); }
    public static void goForumDashboard()     { show("ForumDashboard",       "ACES — Forum"); }
    public static void goTopicCreation()      { show("TopicCreation",        "ACES — New Topic"); }
    public static void goQuizFocusMode()      { show("QuizFocusMode",        "ACES — Quiz"); }
    public static void goQuizResults()        { show("QuizResults",          "ACES — Results"); }
    public static void goQuizManagement()     { show("QuizManagement",       "ACES — Create Quiz"); }
    public static void goParticipationGrading(){ show("ParticipationGrading","ACES — Grading"); }
    public static void goAdminDashboard()     { show("AdminDashboard",       "ACES — Admin"); }
    public static void goAdminAnalytics()     { show("AdminAnalytics",       "ACES — Analytics"); }
    public static void goAdminMembers()       { show("ComplianceMonitoring", "ACES — Members"); }
    public static void goAdminModeration()      { show("AdminModeration",        "ACES — Moderation"); }
    public static void goProfile() {
        if (Session.currentUser() != null && Session.currentUser().getRole() == forum.models.Role.SYSTEM_ADMIN) {
            show("AdminProfileEdit", "ACES — Admin Profile");
        } else {
            show("ProfileEdit", "ACES — Profile");
        }
    }
}

