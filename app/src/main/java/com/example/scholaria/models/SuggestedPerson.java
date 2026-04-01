package com.example.scholaria.models;

public class SuggestedPerson {
    private String name;
    private int imageResId;

    public SuggestedPerson(String name, int imageResId) {
        this.name = name;
        this.imageResId = imageResId;
    }

    public String getName() {
        return name;
    }

    public int getImageResId() {
        return imageResId;
    }
}
