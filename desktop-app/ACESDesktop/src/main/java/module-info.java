module com.example.acesdesktop {
    requires javafx.controls;
    requires javafx.fxml;

    requires org.kordamp.bootstrapfx.core;

    opens com.example.acesdesktop to javafx.fxml;
    exports com.example.acesdesktop;
}