package forum;

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
 * Normal launch opens the Login screen. The developer-only screen-preview
 * gallery is shown ONLY in dev mode, enabled by any of:
 *   - VM option:      -Daces.dev=true
 *   - program arg:    --dev
 *   - env variable:   ACES_DEV=1  (or true)
 * End users never see the gallery.
 *
 * (Design phase — no controller/business logic wired yet.)
 */
public class MainApp extends Application {

    /** Display name -> FXML base name (file in /forum/fxml/). Order = list order. */
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

        if (isDeveloperMode()) {
            startGallery(stage);
        } else {
            startApp(stage);
        }
    }

    /** True only when the developer explicitly opts in to the preview gallery. */
    private boolean isDeveloperMode() {
        if (getParameters() != null && getParameters().getRaw().contains("--dev")) return true;
        String prop = System.getProperty("aces.dev", "");
        String env  = System.getenv().getOrDefault("ACES_DEV", "");
        return prop.equalsIgnoreCase("true") || prop.equals("1")
            || env.equalsIgnoreCase("true")  || env.equals("1");
    }

    /** Normal end-user launch: open the Login screen. */
    private void startApp(Stage stage) {
        try {
            Parent root = FXMLLoader.load(getClass().getResource("/forum/fxml/Login.fxml"));
            stage.setScene(new Scene(root));
            stage.setTitle("ACES");
            stage.show();
        } catch (Exception e) {
            e.printStackTrace();
        }
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

    /** Load a screen's FXML into the preview area, showing any error inline. */
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
