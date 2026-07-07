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
import javafx.fxml.FXMLLoader;
import javafx.scene.layout.BorderPane;
import javafx.scene.layout.Priority;
import javafx.scene.layout.StackPane;
import javafx.scene.layout.VBox;
import javafx.stage.Stage;

import java.util.LinkedHashMap;
import java.util.Map;

/**
 * ACES Desktop — UI preview gallery.
 *
 * Design-phase harness only (no business logic). Launches a single window
 * with a navigator listing every screen; selecting one loads its FXML from
 * /forum/fxml/ into the preview area so all screens can be evaluated in one run.
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
        // --- Navigator (left) ---
        Label navTitle = new Label("ACES · Screen Preview");
        navTitle.setStyle("-fx-font-size:15px; -fx-font-weight:bold; -fx-padding:0 0 4 0;");
        Label navHint = new Label("Select a screen to preview");
        navHint.setStyle("-fx-text-fill:#64748b; -fx-font-size:12px; -fx-padding:0 0 8 0;");

        ListView<String> list = new ListView<>(FXCollections.observableArrayList(SCREENS.keySet()));
        VBox.setVgrow(list, Priority.ALWAYS);
        list.getSelectionModel().selectedItemProperty().addListener(
                (obs, old, name) -> { if (name != null) show(SCREENS.get(name)); });

        VBox nav = new VBox(6, navTitle, navHint, list);
        nav.setPadding(new Insets(20));
        nav.setPrefWidth(260);
        nav.setStyle("-fx-background-color:#ffffff; -fx-border-color:#e2e8f0; -fx-border-width:0 1 0 0;");

        // --- Preview area (right) ---
        preview.setStyle("-fx-background-color:#f8fafc;");
        preview.setAlignment(Pos.TOP_CENTER);
        ScrollPane scroller = new ScrollPane(preview);
        scroller.setFitToWidth(true);
        scroller.setFitToHeight(true);
        scroller.setStyle("-fx-background-color:#f8fafc;");

        BorderPane rootPane = new BorderPane();
        rootPane.setLeft(nav);
        rootPane.setCenter(scroller);

        Scene scene = new Scene(rootPane, 1500, 900);
        stage.setTitle("ACES — UI Preview Gallery");
        stage.setScene(scene);
        stage.show();

        list.getSelectionModel().selectFirst();  // open Login by default
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
