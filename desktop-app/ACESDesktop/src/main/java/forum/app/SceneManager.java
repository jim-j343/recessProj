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

    public static void showHomeFor(forum.models.Role role) {
        switch (role) {
            case LECTURER     -> show("LecturerDashboard", "Smart Discussion Forum — Lecturer");
            case SYSTEM_ADMIN -> show("AdminDashboard",    "Smart Discussion Forum — Admin");
            default           -> show("StudentDashboard",  "Smart Discussion Forum — Student");
        }
    }

    // Named nav helpers keep controllers clean
    public static void goStudentDashboard()   { show("StudentDashboard",    "Smart Discussion Forum — Student"); }
    public static void goLecturerDashboard()  { show("LecturerDashboard",   "Smart Discussion Forum — Lecturer"); }
    public static void goGroups()             { show("GroupsIndex",          "Smart Discussion Forum — Groups"); }
    public static void goGroupShow()          { show("GroupShow",            "Smart Discussion Forum — Group"); }
    public static void goForumDashboard()     { show("ForumDashboard",       "Smart Discussion Forum — Forum"); }
    public static void goTopicCreation()      { show("TopicCreation",        "Smart Discussion Forum — New Topic"); }
    public static void goQuizFocusMode()      { show("QuizFocusMode",        "Smart Discussion Forum — Quiz"); }
    public static void goQuizResults()        { show("QuizResults",          "Smart Discussion Forum — Results"); }
    public static void goQuizManagement()     { show("QuizManagement",       "Smart Discussion Forum — Create Quiz"); }
    public static void goParticipationGrading(){ show("ParticipationGrading","Smart Discussion Forum — Grading"); }
}