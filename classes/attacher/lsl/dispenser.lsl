/*
    Dispenser object for the RP tool! This will rez the objects for attachments and also handle the creation of
    attachment requests!
*/

integer debug = TRUE;

float tick = 5; // How often to check for new agents.

list detectedAgents;
list processedAgents;

key requestAttachment;
key redeliver;
key lastToucher;

integer redeliveryChannel = -223344; // Channel to listen on for redelivery requests.

string url = "https://neckbeardsanon.xen.prgmr.com/Starfall_System/"; // URL to the RP tool's attachment controller.
string module = "attachments/attachment_controller.php?"; // The module we're using, completing the URL.
string hudName = "Starfall HUD";
string titlerName = "Starfall Titler";
string testName = "test";
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

list findAgents()
{
    list tmp;
    if(debug)
    {
        tmp = [(key)llGetOwner()];
    }
    else
    {
        tmp = llGetAgentList(AGENT_LIST_REGION, []);
    }
    // Then filter out the agents we've already processed.
    list result = [];
    integer i;
    for (i = 0; i < llGetListLength(tmp); i++)
    {
        key agent = llList2Key(tmp, i);
        if(!isProcessed(agent))
        {
            result += [agent];
        }
    }
    return result;
}

list yeetProcessedAgents()
{
    // Delete processed agents that are no longer on sim.
    list result = processedAgents;
    integer i;
    for (i = 0; i < llGetListLength(processedAgents); i++)
    {
        key agent = (key)llList2String(processedAgents, i);
        if(llGetAgentSize(agent) == ZERO_VECTOR) // Agents no longer on sim return ZERO_VECTOR.
        {
            result = llDeleteSubList(result, i, i);
        }
    }
    return result;
}

integer isProcessed(key agent)
{
    // Check if an agent is already processed.
    if(llListFindList(processedAgents, [agent]) == -1)
    {
        return FALSE;
    }
    return TRUE;
}

list deleteSpecificAgent(key agent)
{
    // Delete a specific agent from the processed list.
    list result = [];
    integer i;
    for (i = 0; i < llGetListLength(processedAgents); i++)
    {
        key tmp = (key)llList2String(processedAgents, i);
        if (tmp != agent)
        {
            result += [tmp];
        }
    }
    if(llListFindList(result, [agent]) != -1)
    {
        return processedAgents; // Just return the old list if we didn't manage to delete them. Weird!
    }
    return result;
}

rezRpTool(list ids)
{
    // Rez the RP tool.
    integer i;
    for (i = 0; i < llGetListLength(ids); i++)
    {
        // TODO: Rez objects with their rez params.
        llRezObject(testName, llGetPos() + <0.0,0.0,1.0>, <0.0,0.0,0.0>, <0.0,0.0,0.0,1.0>, (integer)llList2String(ids, i));
    }
}

default
{
    state_entry()
    {
        llSetTimerEvent(tick);
        llListen(redeliveryChannel, "", NULL_KEY, "");
    }

    touch_end(integer detected)
    {
        key agent = llDetectedKey(0);
        if(agent == lastToucher)
        {
            return; // Just to prevent spam clicks!
        }
        lastToucher = agent;
        if(llGetAgentSize(agent) != ZERO_VECTOR)
        {
            // Make a redelivery request!
            deleteSpecificAgent(agent);
            redeliver = sendRequest("action=redeliver&uuid=" + (string)agent);
        }
    }

    listen(integer channel, string name, key id, string message)
    {
        // If we get a redelivery request, then we need to send a request to the server to redeliver the attachment.
        if (channel == redeliveryChannel)
        {
            list cmd = llParseStringKeepNulls(message, ["|"], []); // Parse the message.
            if(llList2String(cmd, 0) == "redeliver")
            {
                if(isProcessed((key)llList2String(cmd, 1)))
                {
                    processedAgents = deleteSpecificAgent((key)llList2String(cmd, 1));
                    redeliver = sendRequest("action=redeliver&uuid=" + llList2String(cmd, 1));
                }
            }
        }
    }

    timer()
    {
        llSetTimerEvent(0); // Stop the timer so we can finish all we need to do without racing against the clock.
        // First, delete processed agents that are no longer on sim.
        processedAgents = yeetProcessedAgents();
        // Then find new agents.
        detectedAgents = findAgents();
        // If detectedAgents is  empty...
        if (llGetListLength(detectedAgents) == 0)
        {
            // Then we don't need to ping the server.
            llSetTimerEvent(tick);
            return;
        }
        // Then issue attachment requests for them.
        // We can just make a CSV of all the agents we need to request attachments for.
        string csv = llDumpList2String(detectedAgents, ",");
        // Then add them to the process list.
        processedAgents += detectedAgents;
        // Then send the request.
        requestAttachment = sendRequest("action=attachmentRequest&uuid=" + csv);
        lastToucher = NULL_KEY;
        llSetTimerEvent(tick); // Restart the timer for the next tick.
    }

    http_response(key request, integer status, list meta, string body)
    {
        if(status == 200 || status == 201)
        {
            if(body == "null")
            {
                return; // Do nothing.
            }
            if(request == requestAttachment)
            {
                // Only do things if the status is a success.
                llSetTimerEvent(0); // Pause the server tick so we can do our work.
                list ids = llParseString2List(body, [","], []); // Parse the IDs into our list.
                // Code to rez here.
                // For now we just want to see that this is working so output body.
                rezRpTool(ids); // Rez the RP tool modules for attachment.
                llSetTimerEvent(tick);
            }
            else if(request == redeliver)
            {
                list cmd = llParseStringKeepNulls(body, ["|"], []); // Parse the message.
                if(llList2String(cmd, 0) == "1")
                {
                    llRegionSayTo((key)llList2String(cmd, 1), 0, "Your redelivery has been requested. It should arrive shortly.");
                }
            }
        }
    }

    // Reset the script if the region restarts.
    changed(integer change)
    {
        if (change & CHANGED_REGION_START)
        {
            llResetScript();
        }
    }
}