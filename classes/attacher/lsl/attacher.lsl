/*
    Attacher script for the RP tool!
*/
integer duplicateChannel = -112233; // Channel to listen on for duplicate attachments.
integer rezParam;
integer retries;
integer maxRetries = 3; // Number of times to retry the attachment request.
float timeoutNum = 120.0; // Number of seconds to wait for a response from the server.
string newOwner; // Necessary because llGetOwner() doesn't work right until the script resets, but we need to use their key before then.
string url = "https://neckbeardsanon.xen.prgmr.com/Starfall_System/"; // URL to the RP tool's attachment controller.
string module = "attachments/attachment_controller.php?"; // The module we're using, completing the URL.
key attachmentRequest; // The key of the request we're sending to the server.
key successfulAttach;

string type = "titler"; // hud, if hud. titler, if titler.

// Variable containing the constant for an agent's skull attachment.
integer attach_point = ATTACH_HEAD; // Otherwise we wil use ATTACH_HUD_BOTTOM;

// Function to send a POST request to the server, and receive the response.
// Must support the maximum response size, which is 16KB. Also must ignore SSL type.
// Returns the response as a string.
key sendRequest(string data)
{
    // Create the HTTP request.
    return llHTTPRequest(url + module, [
            HTTP_METHOD, "POST",
            HTTP_BODY_MAXLENGTH, 16384,
            HTTP_MIMETYPE, "application/x-www-form-urlencoded",
            HTTP_CUSTOM_HEADER, "ngrok-skip-browser-warning", "1",
            HTTP_VERIFY_CERT, FALSE
            ], data);
}

default
{
    state_entry()
    {
        // When attached and reset, create a listener that we will use for detaching the RP tool on a duplicate attachment.
        llListen(duplicateChannel, "", "", "");
    }

    attach(key id)
    {
        // On successful attachment, say that to the duplicateChannel.
        llSay(duplicateChannel, type + "|attached");
        // Then indicate the successful attachment to the server.
        successfulAttach = sendRequest("action=iAmAttached&id=" + (string)rezParam + "&uuid=" + newOwner);
        // Then reset script.
        llResetScript();
    }

    listen(integer c, string n, key id, string m)
    {
        if(llGetOwnerKey(id) != llGetOwner())
        {
            return;
        }
        list tmp = llParseStringKeepNulls(m, ["|"], []);
        if(llList2String(tmp, 0) == type)
        {
            if(llList2String(tmp, 1) == "attached")
            {
                // We have a duplicate attachment, so we need to detach the object.
                llRequestPermissions(llGetOwner(), PERMISSION_ATTACH);
            }
        }
    }

    run_time_permissions(integer perm)
    {
        if(perm & PERMISSION_ATTACH)
        {
            llDetachFromAvatar();
        }
    }

    on_rez(integer rez_param)
    {
        // This will send a HTTP request with the rez_param as the id for the attachment,
        // which will hopefully return a valid UUID.
        rezParam = rez_param;
        llSetTimerEvent(timeoutNum);
        attachmentRequest = sendRequest("action=doAttach&id=" + (string)rezParam);
    }

    timer()
    {
        // If we don't get a response in time, kill the object.
        if(!llGetAttached())
        {
            llDie();
        }
        else
        {
            llResetScript(); // If we're attached and the timer is still somehow going, reset script.
        }
    }

    // Events for experience permissions granted and denied.
    experience_permissions(key agent)
    {
        if(!llGetAttached())
        {
            llAttachToAvatarTemp(attach_point);
        }
    }

    experience_permissions_denied(key agent, integer reason)
    {
        // If it's denied for any reason, kill the object.
        // But first inform the person that they have denied the experience.
        llRegionSayTo(agent, 0, "You have denied the experience permissions required to attach the RP tool.");
        llDie();
    }

    http_response(key request, integer status, list metadata, string body)
    {
        // If the request is the attachment request, then we can attach the RP tool.
        // If the request is the successful attachment request, then we can detach the RP tool.
        if(request == attachmentRequest)
        {
            if(status == 499)
            {
                // Retry!
                // First sleep for a second.
                llSleep(1.0);
                retries++;
                if(retries < maxRetries)
                {
                    attachmentRequest = sendRequest("action=doAttach&id=" + (string)rezParam);
                }
                else
                {
                    llDie(); // We've retried too many times, so kill the object.
                }
            }
            // Otherwise if the status is anything not a success, just die.
            else if(status != 200 && status != 201)
            {
                llOwnerSay("Error: HTTP request failed with status " + (string)status); // Notify the owner of the error
                llDie(); // Kill the object.
            }
            else
            {
                // If the body is "die", then we missed our window and we should kill the object.
                // Otherwise, we should attach the RP tool.
                body = llStringTrim(body, STRING_TRIM);
                if(body == "die")
                {
                    llDie(); // Die means we missed our window. Kill object.
                }
                else
                {
                    // Validate that the UUID received is an agent and that they are present on sim.
                    // We can use agent height to do this! zero_vector means the agent is not there.
                    list data = llParseStringKeepNulls(body, ["|"], []); // Split the body into a list.
                    // UUID will be index 0, attach point 1, position 2, rotation 3.
                    if(llGetAgentSize(llList2String(data, 0)) == ZERO_VECTOR)
                    {
                        llDie(); // Kill object.
                    }
                    else
                    {
                        // Now we request experience permissions for the UUID.
                        // If we get them, we can attach the RP tool.
                        newOwner = llList2String(data, 0);
                        llRequestExperiencePermissions(newOwner, ""); // Unused string var at the end because LL is LL.
                    }
                }
            }
        }
    }
}