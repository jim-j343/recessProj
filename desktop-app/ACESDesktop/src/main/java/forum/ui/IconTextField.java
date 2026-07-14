package forum.ui;

import javafx.geometry.Insets;
import javafx.geometry.Pos;
import javafx.scene.control.TextField;
import javafx.scene.layout.HBox;
import javafx.scene.layout.Priority;
import javafx.scene.paint.Color;
import javafx.scene.shape.SVGPath;
import javafx.scene.shape.StrokeLineCap;

/**
 * A text field with a leading outline icon inside the field border, matching
 * the portal-style login inputs.
 *
 *   <IconTextField fx:id="emailField" icon="mail" promptText="you@example.com"/>
 *
 * Supported icon names: "user", "mail", "id", "lock". UI only.
 */
public class IconTextField extends HBox {

    private static final String USER =
        "M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2 M16 7a4 4 0 1 0-8 0 4 4 0 1 0 8 0";
    private static final String MAIL =
        "M2 6a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2z M22 7l-10 6L2 7";
    private static final String ID =
        "M4 5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z "
      + "M9 9a2 2 0 1 0 4 0 2 2 0 1 0-4 0 M7 17a4 4 0 0 1 8 0 M16 9h2 M16 13h2";
    private static final String LOCK =
        "M6 11a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2z M8 9V7a4 4 0 0 1 8 0v2";

    private final SVGPath icon = new SVGPath();
    private String iconName = "user";
    private final TextField field = new TextField();

    public IconTextField() {
        getStyleClass().add("reveal-field");
        setAlignment(Pos.CENTER_LEFT);

        icon.setContent(USER);
        icon.setFill(Color.TRANSPARENT);
        icon.setStroke(Color.web("#64748b"));
        icon.setStrokeWidth(1.7);
        icon.setStrokeLineCap(StrokeLineCap.ROUND);
        icon.setScaleX(0.8);
        icon.setScaleY(0.8);
        HBox iconBox = new HBox(icon);
        iconBox.setAlignment(Pos.CENTER);
        iconBox.setPadding(new Insets(0, 2, 0, 12));

        field.getStyleClass().add("reveal-input");
        HBox.setHgrow(field, Priority.ALWAYS);

        getChildren().addAll(iconBox, field);
    }

    /** icon = user | mail | id | lock */
    public final void setIcon(String name) {
        String n = name == null ? "" : name.trim().toLowerCase();
        switch (n) {
            case "mail":  icon.setContent(MAIL); break;
            case "id":    icon.setContent(ID);   break;
            case "lock":  icon.setContent(LOCK); break;
            case "user":
            default:      n = "user"; icon.setContent(USER); break;
        }
        iconName = n;
    }

    public final String getIcon() {
        return iconName;
    }

    public final void setPromptText(String value) { field.setPromptText(value); }
    public final String getPromptText() { return field.getPromptText(); }
    public final void setText(String value) { field.setText(value); }
    public final String getText() { return field.getText(); }
    public TextField getField() { return field; }
}
