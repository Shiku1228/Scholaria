package com.example.scholaria.models;

public class Assignment {
    private String title;
    private String subject;
    private String deadline;

    public Assignment(String title, String subject, String deadline) {
        this.title = title;
        this.subject = subject;
        this.deadline = deadline;
    }

    // Adding a 2-argument constructor for convenience
    public Assignment(String subject, String deadline) {
        this.title = "Assignment";
        this.subject = subject;
        this.deadline = deadline;
    }

    public String getTitle() {
        return title;
    }

    public String getSubject() {
        return subject;
    }

    public String getDeadline() {
        return deadline;
    }
}
