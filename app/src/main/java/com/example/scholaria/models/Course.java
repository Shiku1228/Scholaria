package com.example.scholaria.models;

public class Course {
    private String title;
    private String subtitle;
    private String semester;
    private String year;
    private int progress;
    private String assignments;

    public Course(String title, String subtitle, String semester, String year, int progress, String assignments) {
        this.title = title;
        this.subtitle = subtitle;
        this.semester = semester;
        this.year = year;
        this.progress = progress;
        this.assignments = assignments;
    }

    public String getTitle() { return title; }
    public String getSubtitle() { return subtitle; }
    public String getSemester() { return semester; }
    public String getYear() { return year; }
    public int getProgress() { return progress; }
    public String getAssignments() { return assignments; }
}
