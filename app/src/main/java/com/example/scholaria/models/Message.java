package com.example.scholaria.models;

public class Message {
    private String senderName;
    private String lastMessage;
    private String time;
    private int unreadCount;
    private int profileImageResId;

    public Message(String senderName, String lastMessage, String time, int unreadCount, int profileImageResId) {
        this.senderName = senderName;
        this.lastMessage = lastMessage;
        this.time = time;
        this.unreadCount = unreadCount;
        this.profileImageResId = profileImageResId;
    }

    public String getSenderName() { return senderName; }
    public String getLastMessage() { return lastMessage; }
    public String getTime() { return time; }
    public int getUnreadCount() { return unreadCount; }
    public int getProfileImageResId() { return profileImageResId; }
}
