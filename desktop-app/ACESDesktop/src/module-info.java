module com.example.acesdesktop {
    requires javafx.controls;
    requires javafx.fxml;

    requires org.kordamp.bootstrapfx.core;
    requires java.desktop;

    opens forum to javafx.fxml;
    opens forum.ui to javafx.fxml;
    exports forum;
    exports forum.ui;
}