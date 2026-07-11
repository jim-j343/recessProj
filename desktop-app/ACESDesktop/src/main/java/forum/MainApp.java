package forum;

import forum.app.SceneManager;
import forum.database.SQLiteConnection;
import forum.database.TopicDao;
import forum.database.UserDao;

import javafx.application.Application;
import javafx.collections.FXCollections;
import javafx.geometry.Insets;
import javafx.geometry.Pos;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.Label;
import javafx.scene.control.ListView;
import javafx.scene.control.ScrollPane;
import javafx.scene.image.Image;
import javafx.fxml.FXMLLoader;
import javafx.scene.layout.BorderPane;
import javafx.scene.layout.Priority;
import javafx.scene.layout.StackPane;
import javafx.scene.layout.VBox;
import javafx.stage.Stage;

import java.util.LinkedHashMap;
import java.util.Map;

/**
 * ACES Desktop application entry point.
 *
 * Normal launch initialises the local cache and opens the Login screen.
 * The developer-only screen-preview gallery is shown ONLY in dev mode, via:
 *   -Daces.dev=true  |  program arg --dev  |  env ACES_DEV=1
 */
public class MainApp extends Application {

    private static final Map<String, String> SCREENS = new LinkedHashMap<>();
    static {
        SCREENS.put("01 · Login",                  "Login");
        SCREENS.put("02 · Register / Onboarding",  "Register");
        SCREENS.put("03 · Forum Dashboard",        "ForumDashboard");
        SCREENS.put("04 · Admin Analytics",        "AdminAnalytics");
        SCREENS.put("05 · Quiz Management",        "QuizManagement");
        SCREENS.put("06 · Quiz Focus Mode",        "QuizFocusMode");
        SCREENS.put("07 · Participation Grading",  "ParticipationGrading");
        SCREENS.put("08 · Compliance Monitoring",  "ComplianceMonitoring");
        SCREENS.put("09 · Student Assessment",     "StudentAssessment");
        SCREENS.put("10 · Topic Creation",         "TopicCreation");
        SCREENS.put("11 · Topic Detail",           "TopicDetail");
        SCREENS.put("12 · Student Dashboard",      "StudentDashboard");
        SCREENS.put("13 · Lecturer Dashboard",     "LecturerDashboard");
        SCREENS.put("14 · Admin Dashboard",        "AdminDashboard");
        SCREENS.put("15 · Groups",                 "GroupsIndex");
        SCREENS.put("16 · Create Group",           "GroupCreate");
        SCREENS.put("17 · Group Detail",           "GroupShow");
        SCREENS.put("18 · Quiz Results",           "QuizResults");
        SCREENS.put("19 · Profile",                "ProfileEdit");
        SCREENS.put("20 · Forgot Password",        "ForgotPassword");
        SCREENS.put("21 · Reset Password",         "ResetPassword");
        SCREENS.put("22 · Confirm Password",       "ConfirmPassword");
        SCREENS.put("23 · Verify Email",           "VerifyEmail");
    }

    private final StackPane preview = new StackPane();

    public static void main(String[] args) {
        launch(args);
    }

    @Override
    public void start(Stage stage) {
        try {
            stage.getIcons().add(new Image(
                    getClass().getResourceAsStream("/forum/images/aces-logo-256.png")));
        } catch (Exception ignored) { }

        // Initialise the local SQLite cache and seed demo accounts.
        try {
            SQLiteConnection.initSchema();
            new UserDao().seedDemoUsers();
            new TopicDao().seedDemoIfEmpty();
        } catch (Exception e) {
            e.printStackTrace();
        }

        SceneManager.init(stage);

        if (isDeveloperMode()) {
            startGallery(stage);
        } else {
            SceneManager.show("Login", "ACES");
        }
    }

    private boolean isDeveloperMode() {
        if (getParameters() != null && getParameters().getRaw().contains("--dev")) return true;
        String prop = System.getProperty("aces.dev", "");
        String env  = System.getenv().getOrDefault("ACES_DEV", "");
        return prop.equalsIgnoreCase("true") || prop.equals("1")
            || env.equalsIgnoreCase("true")  || env.equals("1");
    }

    /** Developer-only screen-preview gallery. */
    private void startGallery(Stage stage) {
        Label navTitle = new Label("ACES · Screen Preview (dev)");
        navTitle.setStyle("-fx-font-size:15px; -fx-font-weight:bold; -fx-padding:0 0 4 0;");
        Label navHint = new Label("Developer preview — not shown to users");
        navHint.setStyle("-fx-text-fill:#64748b; -fx-font-size:12px; -fx-padding:0 0 8 0;");

        ListView<String> list = new ListView<>(FXCollections.observableArrayList(SCREENS.keySet()));
        VBox.setVgrow(list, Priority.ALWAYS);
        list.getSelectionModel().selectedItemProperty().addListener(
                (obs, old, name) -> { if (name != null) show(SCREENS.get(name)); });

        VBox nav = new VBox(6, navTitle, navHint, list);
        nav.setPadding(new Insets(20));
        nav.setPrefWidth(260);
        nav.setStyle("-fx-background-color:#ffffff; -fx-border-color:#e2e8f0; -fx-border-width:0 1 0 0;");

        preview.setStyle("-fx-background-color:#f8fafc;");
        preview.setAlignment(Pos.TOP_CENTER);
        ScrollPane scroller = new ScrollPane(preview);
        scroller.setFitToWidth(true);
        scroller.setFitToHeight(true);
        scroller.setStyle("-fx-background-color:#f8fafc;");

        BorderPane rootPane = new BorderPane();
        rootPane.setLeft(nav);
        rootPane.setCenter(scroller);

        stage.setScene(new Scene(rootPane, 1500, 900));
        stage.setTitle("ACES — UI Preview Gallery (developer)");
        stage.show();
        list.getSelectionModel().selectFirst();
    }

    private void show(String fxml) {
        preview.getChildren().clear();
        try {
            Parent screen = FXMLLoader.load(
                    getClass().getResource("/forum/fxml/" + fxml + ".fxml"));
            preview.getChildren().add(screen);
        } catch (Exception e) {
            Label err = new Label("Could not load " + fxml + ".fxml\n\n" + e.getMessage());
            err.setStyle("-fx-text-fill:#dc2626; -fx-padding:32; -fx-font-size:13px;");
            err.setWrapText(true);
            preview.getChildren().add(err);
            e.printStackTrace();
        }
    }
}
