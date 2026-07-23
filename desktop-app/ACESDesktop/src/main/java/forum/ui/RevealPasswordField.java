package forum.ui;

import javafx.geometry.Insets;
import javafx.geometry.Pos;
import javafx.scene.control.PasswordField;
import javafx.scene.control.TextField;
import javafx.scene.control.ToggleButton;
import javafx.scene.layout.HBox;
import javafx.scene.layout.Priority;
import javafx.scene.paint.Color;
import javafx.scene.shape.SVGPath;
import javafx.scene.shape.StrokeLineCap;

/**
 * Password field with a leading lock icon and a trailing eye (show/hide) toggle.
 * Masked {@link PasswordField} + plain {@link TextField} bound together.
 * UI only — no validation or auth logic.
 */
public class RevealPasswordField extends HBox {

    private static final String LOCK =
        "M6 11a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2z "
      + "M8 9V7a4 4 0 0 1 8 0v2";
    private static final String EYE_OFF =
        "M9.88 9.88a3 3 0 1 0 4.24 4.24 "
      + "M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68 "
      + "M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61 "
      + "M2 2 L22 22";
    private static final String EYE_OPEN =
        "M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z "
      + "M15 12a3 3 0 1 0-6 0 3 3 0 1 0 6 0";

    private final PasswordField password = new PasswordField();
    private final TextField plain = new TextField();
    private final ToggleButton reveal = new ToggleButton();
    private final SVGPath eye = new SVGPath();

    public RevealPasswordField() {
        getStyleClass().add("reveal-field");
        setAlignment(Pos.CENTER_LEFT);

        // leading lock icon
        SVGPath lock = new SVGPath();
        lock.setContent(LOCK);
        lock.setFill(Color.TRANSPARENT);
        lock.setStroke(Color.web("#64748b"));
        lock.setStrokeWidth(1.7);
        lock.setStrokeLineCap(StrokeLineCap.ROUND);
        lock.setScaleX(0.75);
        lock.setScaleY(0.75);
        HBox lockBox = new HBox(lock);
        lockBox.setAlignment(Pos.CENTER);
        lockBox.setPadding(new Insets(0, 2, 0, 12));

        plain.textProperty().bindBidirectional(password.textProperty());
        plain.setManaged(false);
        plain.setVisible(false);
        password.getStyleClass().add("reveal-input");
        plain.getStyleClass().add("reveal-input");

        // trailing eye toggle
        eye.setContent(EYE_OFF);
        eye.setFill(Color.TRANSPARENT);
        eye.setStroke(Color.web("#64748b"));
        eye.setStrokeWidth(1.7);
        eye.setStrokeLineCap(StrokeLineCap.ROUND);
        eye.setScaleX(0.8);
        eye.setScaleY(0.8);
        reveal.setGraphic(eye);
        reveal.getStyleClass().add("reveal-toggle");
        reveal.setFocusTraversable(false);

        HBox.setHgrow(password, Priority.ALWAYS);
        HBox.setHgrow(plain, Priority.ALWAYS);

        reveal.selectedProperty().addListener((obs, wasSel, isSel) -> {
            plain.setVisible(isSel);
            plain.setManaged(isSel);
            password.setVisible(!isSel);
            password.setManaged(!isSel);
            eye.setContent(isSel ? EYE_OPEN : EYE_OFF);
            eye.setStroke(Color.web(isSel ? "#0f172a" : "#64748b")); // black when shown
            (isSel ? plain : password).requestFocus();
        });

        getChildren().addAll(lockBox, password, plain, reveal);
    }

    public final void setPromptText(String value) { password.setPromptText(value); plain.setPromptText(value); }
    public final String getPromptText() { return password.getPromptText(); }
    public final void setText(String value) { password.setText(value); }
    public final String getText() { return password.getText(); }
    public PasswordField getPasswordField() { return password; }
}
