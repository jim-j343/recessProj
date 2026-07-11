package forum.app;

import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.stage.Stage;

import java.io.IOException;

/** Central navigation: loads FXML screens from /forum/fxml/ into the primary stage. */
public final class SceneManager {

    private static Stage stage;

    private SceneManager() { }

    public static void init(Stage primaryStage) {
        stage = primaryStage;
    }

    /** Load and show a screen by its FXML base name (e.g. "Login", "ForumDashboard"). */
    public static void show(String fxmlName) {
        show(fxmlName, "ACES");
    }

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
            stage.sizeToScene();      // fit the window to each screen's preferred size
            stage.centerOnScreen();
            stage.show();
        } catch (IOException e) {
            e.printStackTrace();
        }
    }

    /** Route a freshly authenticated user to their role's home screen. */
    public static void showHomeFor(forum.models.Role role) {
        switch (role) {
            case LECTURER     -> show("LecturerDashboard", "Smart Discussion Forum — Lecturer");
            case SYSTEM_ADMIN -> show("AdminDashboard", "Smart Discussion Forum — Admin");
            case STUDENT      -> show("StudentDashboard", "Smart Discussion Forum — Student");
            default           -> show("StudentDashboard", "Smart Discussion Forum");
        }
    }
}
