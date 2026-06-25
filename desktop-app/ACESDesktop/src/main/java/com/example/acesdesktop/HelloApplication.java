package com.example.acesdesktop;

import javafx.application.Application;
import javafx.fxml.FXMLLoader;
import javafx.scene.Scene;
import javafx.scene.image.Image;
import javafx.scene.input.KeyCombination;
import javafx.scene.paint.Color;
import javafx.scene.text.Text;
import javafx.stage.Stage;


import java.io.IOException;

public class HelloApplication extends Application {
    @Override
    public void start(Stage stage) throws IOException {
        
        FXMLLoader fxmlLoader = new FXMLLoader(HelloApplication.class.getResource("hello-view.fxml"));
        Scene scene = new Scene(fxmlLoader.load(), 400, 400, Color.BEIGE);


        stage.setTitle("Hello Program woo!");

        Image icon = new Image("icon.png");
        stage.getIcons().add(icon);

        Text text = new Text("Hiiiiihiiiii!");0

        stage.setWidth(400);
        stage.setHeight(200);
        stage.setResizable(false);
        stage.setFullScreen(false);
        stage.setFullScreenExitHint("You can't escape unless you press q");
        stage.setFullScreenExitKeyCombination(KeyCombination.valueOf("q"));




        stage.setScene(scene);
        stage.show();


    }

    public static void main(String[] args) {
        launch(args);
    }
}

