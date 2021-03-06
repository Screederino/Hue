<?php exit() ?>--by elretardlol 70.79.64.24
require 'ezEvadeLibrary'

local sVersion = 3.0

local evadeLib = ezEvadeLibrary()

local objSkillShots = {}
local procSkillShots = {}

local AllyIndex = {}
local EnemyIndex = {}

local skillShots = {}

local isDodging = true
local heroHoldPosition = true

local lastMovePos = heroPoint
local lastBestLastMove = nil
local lastUserMoveCommand = 0

local lastMoveCommand = 0
local lastBestPosition = nil

local lastBlockedPos = nil
local lastBlockedTime = 0

local lastEvadePos = nil

local lastTickCount = 0

local heroPoint = Point(myHero.visionPos.x, myHero.visionPos.z)

local wayPointManager = WayPointManager()

local allMinions = minionManager(MINION_ALL, 500, player, nil)

--local oldMoveTo = myHero:MoveTo

function DeleteSpell(spellName)
	local spell = skillShots[spellName]
	
	if spell and spell.spellParticle then
		return
	else
	skillShots[spellName] = nil
	end
end

function CheckCasterDead(spell)
	if spell.charIndex then
		local hero = heroManager:GetHero(spell.charIndex)
		if hero.dead then
			skillShots[spell.info.spellName] = nil
			return true
		end
	end
	
	return false
end

function UpdateSpellEndPos(spellName)

	if skillShots[spellName] == nil or skillShots[spellName].dirSet then
		return
	end
		
	local spell = skillShots[spellName]
	local spellParticle = skillShots[spellName].spellParticle	

	if CheckCasterDead(spell) then return end
	
	if spellParticle then
	
	local startPos = spell.startPos
	local cPos = spellParticle
	
	local lastPos = skillShots[spellName].lastPos or startPos
	local dir = (Vector(cPos.x,0,cPos.z) - Vector(lastPos.x,0,lastPos.z)):normalized()
		
	if spell.info.type == "LINE" then			
	
		if spell.endPos then
		local testDir = (Vector(spell.endPos.x,0,spell.endPos.z) - Vector(startPos.x,0,startPos.z)):normalized()
		
		if skillShots[spellName].dir and skillShots[spellName].dir:dotP(dir) > .99 and dir:dotP(testDir) < .90 then
			spell.endPos = Vector(startPos) + dir * spell.info.range
			skillShots[spellName].dirSet = true
		else
			skillShots[spellName].dir = dir
			DelayAction(UpdateSpellEndPos, .02, {spellName}) --needs constant checking
		end		
	else
	
	if skillShots[spellName].dir and skillShots[spellName].dir:dotP(dir) > .99 and skillShots[spellName].dirSet == nil then	
		spell.endPos = Vector(startPos) + dir * spell.info.range
		skillShots[spellName].dirSet = true
	else
		skillShots[spellName].dir = dir
		DelayAction(UpdateSpellEndPos, .02, {spellName}) --needs constant checking
	end
	
	end
	
	skillShots[spellName].lastPos = Vector(spellParticle.x,0,spellParticle.z)
	
	
	
	else	
	if spell.endPos == nil then
	spell.endPos = Vector(spellParticle)
	end
	
	end
	
	lastBestPosition = GetBestPosition()
	end
end

function IsAllySpell(object, spellInfo)
	local charName = spellInfo.charName

	for i = 1, heroManager.iCount do
		local hero = heroManager:GetHero(i)		
		if hero.team == player.team and charName == hero.charName then
			if spellInfo.type == "LINE" and GetDistance(object, hero) < 50 then
				return true
			elseif spellInfo.type == "CIRCULAR" and GetDistance(object, hero) < spellInfo.range then
				return true
			end
		end
	end
	
	return false
end

function GetHeroIndex( heroName)
	for i = 1, heroManager.iCount do
		local hero = heroManager:GetHero(i)		
		if hero.name == heroName then
			return i
		end
	end
end

function OnProcessSpell(hero, spell)

	if hero.type == "obj_AI_Hero" and procSkillShots[spell.name] then
		if hero.team ~= player.team then
		
		local spellInfo = procSkillShots[spell.name]
		
		if Menu.SkillSettings[spellInfo.charName .. "SkillSettings"][spellInfo.spellName] == false then return end
		
		if GetDistance(myHero, spell.startPos) < spellInfo.range + 1000 then
		
		local endPosition = Vector(spell.endPos)
		local endTick = 0
		
		--print(spell.projectileID)
		
		if spellInfo.type == "LINE" then
			endTick = spellInfo.spellDelay + spellInfo.range / spellInfo.projectileSpeed * 1000
			
			local direction = (Vector(spell.endPos.x,0,spell.endPos.z) - Vector(spell.startPos.x,0,spell.startPos.z)):normalized()
			endPosition = Vector(spell.startPos) + direction * spellInfo.range
		else
			endTick = spellInfo.spellDelay
		end
				
		skillShots[spell.name] = {startTime = GetTick(), endTime = GetTick() + endTick, startPos = Vector(spell.startPos), endPos = endPosition, info = spellInfo, charIndex = EnemyIndex[spellInfo.charName]}
		DelayAction(DeleteSpell, endTick/1000, {spell.name})
		lastBestPosition = GetBestPosition()
		
		end
		
		else
		--allySkillShots[spell.name] = {startTime = GetTick(), endPos}
		--DelayAction(DeleteSpell, endTick/1000, {spell.name})
		end		
	end
end

function OnCreateObj(object)

	if objSkillShots[object.name] then
	
		local spellInfo = objSkillShots[object.name]
		
		if Menu.SkillSettings[spellInfo.charName .. "SkillSettings"][spellInfo.spellName] == false then return end
		
		if IsAllySpell(object,spellInfo) then --object placement?
			return
		end		
		
		--print(object.name)
		--print(GetDistance(object,myHero))
		
		if GetDistance(myHero, object) < spellInfo.range + 1000 and skillShots[spellInfo.spellName] == nil then
				
		local endTick = 0
		local endPosition = nil
		
		if spellInfo.type == "LINE" then
			endTick = (spellInfo.range / spellInfo.projectileSpeed) * 1000
		else
			endTick = spellInfo.spellDelay
			endPosition = Vector(object)
		end		

		skillShots[spellInfo.spellName] = {startTime = GetTick(), endTime = GetTick() + endTick, startPos = Vector(object.x,myHero.y,object.z), endPos = endPosition, info = spellInfo, spellParticle = object, charIndex = EnemyIndex[spellInfo.charName]}
		DelayAction(UpdateSpellEndPos, .02, {spellInfo.spellName}) --needs constant checking
		DelayAction(DeleteSpell, endTick/1000, {spellInfo.spellName})
					
		elseif skillShots[spellInfo.spellName] then		
			skillShots[spellInfo.spellName].spellParticle = object
		end
	end
end

function OnDeleteObj(object)
	if objSkillShots[object.name] then	
		for spellName, spell in pairs(skillShots) do			
			if spell.spellParticle and object.name == spell.spellParticle.name and GetDistance(spell.spellParticle, object) < 25 then
				skillShots[spellName] = nil
			end		
		end		
	end
end

function canHeroEvade(hero, spell)
	local radius = Menu.DodgeBuffer

	if spell.startPos and spell.endPos then

	local evadeTime = 0
	local spellHitTime = math.huge	
	
	if spell.info.type == "LINE" then		
		local startPos = Point(spell.startPos.x, spell.startPos.z)
		local endPos = Point(spell.endPos.x, spell.endPos.z)
		local projection = heroPoint:perpendicularFoot(Line(startPos, endPos))
		local spellPos = Point(spell.startPos.x,spell.startPos.z)
		
		evadeTime = 1000 * math.max(0,(GetSpellRadius(spell)) - projection:distance(heroPoint))/hero.ms

		if spell.spellParticle then
		spellPos = Point(spell.spellParticle.x,spell.spellParticle.z)
		spellHitTime = 1000 * projection:distance(spellPos)/spell.info.projectileSpeed
		else
		spellHitTime = spell.info.spellDelay - (GetTick() - spell.startTime) + 1000 * projection:distance(spellPos)/spell.info.projectileSpeed
		end
				
		if spell.info.projectileSpeed == math.huge then --fix this part
		spellHitTime = spell.info.spellDelay - (GetTick() - spell.startTime)
		end
		
	else
		local endPos = Point(spell.endPos.x, spell.endPos.z)
		evadeTime = 1000 * math.max(0,(GetSpellRadius(spell) + radius) - endPos:distance(heroPoint))/hero.ms
		spellHitTime = spell.info.spellDelay - (GetTick() - spell.startTime)	
	end
		
	return spellHitTime > evadeTime, (spellHitTime - evadeTime)
	
	end
	
	return true
end

function canSpellHit(spell, pos, timeElapsed)
	local radius = Menu.DodgeBuffer

	if spell.startPos and spell.endPos then
	
	if spell.info.type == "LINE" then		
		local startPos = Point(spell.startPos.x, spell.startPos.z)
		local endPos = Point(spell.endPos.x, spell.endPos.z)
		local dir = (endPos - startPos):normalized()		
		if (endPos - startPos) == Point(0,0) then
			dir = Point(0,0)
		end

		
		local spellTime = math.max(0,(GetTick()-spell.startTime) + timeElapsed - spell.info.spellDelay)		
		local spellPos = startPos + dir * spell.info.projectileSpeed * (spellTime/1000)

		if spell.spellParticle then
		spellTime = timeElapsed
		spellPos = Point(spell.spellParticle.x,spell.spellParticle.z) + dir * spell.info.projectileSpeed * (spellTime/1000)
		end
		
		return pos:distance(spellPos) <= GetSpellRadius(spell) + radius	
	else
		local spellFutureTick = GetTick() + timeElapsed
		local spellHitTime = spell.startTime + spell.info.spellDelay
		local endPos = Point(spell.endPos.x, spell.endPos.z)

		return pos:distance(endPos) <= GetSpellRadius(spell) + radius 
			and spellFutureTick > spellHitTime
	end
		
	
	end
	
	return false
end

function GetSpellRadius(spell)
	if spell.info.spellName == 'test' then
		return spell.info.radius
	end
	
	return Menu.SkillSettings[spell.info.charName .. "SkillSettings"][spell.info.spellName .. "buffer"]
end

function playerInSkillShot(spell)
	return inSkillShot(spell, Point(myHero.visionPos.x, myHero.visionPos.z), Menu.DodgeBuffer)
end

function playerDistanceToSpell(spell, pos)
	if spell.startPos and spell.endPos then
	
	if pos then heroPoint = pos end
	
	local spellPos = Point(spell.startPos.x, spell.startPos.z)
	local endPos = Point(spell.endPos.x, spell.endPos.z)
			
	if spell.info.type == "LINE" then				
		local projection = heroPoint:perpendicularFoot(Line(spellPos, endPos))
		return heroPoint:distance(projection) 
	else
		return heroPoint:distance(endPos)
	end
	
	end
end


function inSkillShot(spell, position, radius)

	if Menu.Undodgeable and not canHeroEvade(myHero, spell) then
		--print('cannot evade')
		return false
	end
	
	local spellPos = Point(spell.startPos.x, spell.startPos.z)
	local endPos = Point(spell.endPos.x, spell.endPos.z)
			
	if spell.info.type == "LINE" then	
		if spell.spellParticle then --leave little space at back of skillshot
			local dir = (Vector(spell.endPos.x,0,spell.endPos.z) - Vector(spell.startPos.x,0,spell.startPos.z)):normalized()
			spellPos = Vector(spell.startPos) + dir * (GetDistance(spell.spellParticle, spell.startPos) - GetSpellRadius(spell))
			spellPos = Point(spellPos.x,spellPos.z)
		end
		
		local skillshotSegment = LineSegment(spellPos, endPos)
		local projection = position:perpendicularFoot(Line(spellPos, endPos))

		return position:distance(projection) <= GetSpellRadius(spell) + radius and skillshotSegment:distance(projection) < 3
	else
		return position:distance(endPos) <= GetSpellRadius(spell) + radius
	end
end

function GetLinePosition(spell)
	local spellPos = Point(spell.startPos.x, spell.startPos.z)
	local endPos = Point(spell.endPos.x, spell.endPos.z)

	local projection = heroPoint:perpendicularFoot(Line(spellPos, endPos))	
	local dir = (heroPoint - projection):normalized()	
			
	return projection + dir * (GetSpellRadius(spell) + Menu.DodgeBuffer+50)
end

function GetCircularPosition(spell)
	local endPos = Point(spell.endPos.x,spell.endPos.z)
	local dir = (heroPoint - endPos):normalized()
	
	return endPos + dir * (GetSpellRadius(spell) + Menu.DodgeBuffer+50)
end

function isDangerousPos(pos)
	for spellName, spell in pairs(skillShots) do
		if spell.startPos and spell.endPos then
			if inSkillShot(spell, pos, Menu.DodgeBuffer) then
				return true
			end
		end
	end
	
	return false
end

function GetBestPosition()
	local bestPosition = nil
	local closestPosition = math.huge
		
	if lastBestPosition and not isDangerousPos(lastBestPosition) and canHeroWalkToPos(lastBestPosition) then
	bestPosition = lastBestPosition
	
	if lastMovePos then
	closestPosition = lastMovePos:distance(lastBestPosition)	
	end
	end
	
	local posChecked = 0	
	local posRadius = 50	
	local radiusIndex = 0
	
	local backupPos = nil
	
	while posChecked < 50 do
	radiusIndex = radiusIndex + 1
	
	local curRadius = radiusIndex * (2*posRadius)
	local curCircleChecks = math.ceil((2*math.pi*curRadius)/(2*posRadius))
	
	for i=1, curCircleChecks, 1 do
	
	posChecked = posChecked + 1
	local cRadians = (2*math.pi/(curCircleChecks-1))*i
	local pos = Point(myHero.x + curRadius*math.cos(cRadians), myHero.z + curRadius*math.sin(cRadians) )
	local inDanger = false
				
	if not CheckWallCollision(pos) then
	
	inDanger = isDangerousPos(pos)
	
	
	if not inDanger and canHeroWalkToPos(pos) and (Menu.UnitCollision == false or CheckUnitCollision(pos) == false) then
			
		if lastMovePos and lastMovePos:distance(pos) < closestPosition then
			bestPosition = pos
			closestPosition = lastMovePos:distance(pos)			
		elseif lastMovePos == nil then
			return pos
		end
		
		if backupPos == nil then
			backupPos = pos
		end
	end
	
	end	
	end	
	end
	
	if bestPosition == nil then
		bestPosition = backupPos
	end
		
	return bestPosition	
end

function CheckUnitCollision(pos)
	for _, minion in pairs(allMinions.objects) do	
		if pos:distance(Point(minion.x,minion.z)) < 75 then
			return true
		end
	end
	
	for i = 1, heroManager.iCount do
	local hero = heroManager:GetHero(i)		
		if hero.name ~= myHero.name and pos:distance(Point(hero.x,hero.z)) < 75 then
			return true
		end		
	end	
	
	return false
end

function CheckWallCollision(pos)
	if Menu.CheckWall then
		for i=1, 5, 1 do
			local cRadians = (2*math.pi/5)*i
			local tPos = Point(pos.x + 75*math.cos(cRadians), pos.y + 75*math.sin(cRadians) )

			if IsWall(D3DXVECTOR3(tPos.x, myHero.y, tPos.y)) then
				return true
			end
		end
		
		return false
	else
		return IsWall(D3DXVECTOR3(pos.x, myHero.y, pos.y))
	end
end

function canHeroWalkToPos(pos)
	for spellName, spell in pairs(skillShots) do
		if spell.startPos and spell.endPos then
			if GetSpellCollisionTimeToPos(spell, pos) then
				return false
			end
		end
	end
	
	return true
end

function GetSpellCollisionTimeToPos (spell, pos)
	local walkDir = (pos - heroPoint):normalized()
	
	if (pos - heroPoint) == Point(0,0) then
		walkDir = Point(0,0)
	end
	
	if spell.startPos and spell.endPos then
	
	if spell.info.type == "LINE" then
	
	local startPos = Point(spell.startPos.x, spell.startPos.z)
	local endPos = Point(spell.endPos.x, spell.endPos.z)
	
	local dir = (endPos - startPos):normalized()	
	if (endPos - startPos) == Point(0,0) then
		dir = Point(0,0)
	end
	
	local spellTime = (GetTick()-spell.startTime) - spell.info.spellDelay		
	local spellPos = startPos + dir * spell.info.projectileSpeed * (spellTime/1000)
		
	if spell.spellParticle then
	spellPos = Point(spell.spellParticle.x,spell.spellParticle.z)
	end
	
	local timeToCollision = GetTimeToCollision2 (heroPoint, spellPos, walkDir * myHero.ms, dir * spell.info.projectileSpeed, Menu.DodgeBuffer, spell.info.radius)
	
	--[[
	if timeToCollision and timeToCollision > 0 then
	--print(timeToCollision)
	--spellPos = spellPos + dir * spell.info.projectileSpeed * timeToCollision
	--DrawCircle(spellPos.x, myHero.y, spellPos.y, 50, 0x00FF00)
	print(timeToCollision)
	local heroPos = Vector(myHero) + VectorObj(walkDir) * myHero.ms * timeToCollision
	DrawCircle(heroPos.x, myHero.y, heroPos.z, 50, 0x00FF00)
	end]]	
		if timeToCollision and timeToCollision > 0 then
			return true
		end		
	else	
		local endPos = Point(spell.endPos.x, spell.endPos.z)		
		
		if spell.spellParticle then
		endPos = Point(spell.spellParticle.x,spell.spellParticle.z)
		end
		
		local timeToCollision = GetTimeToCollision2 (heroPoint, endPos, walkDir * myHero.ms, Point(0,0), Menu.DodgeBuffer, spell.info.radius)
		local spellHitTime = spell.startTime + spell.info.spellDelay

		if timeToCollision and timeToCollision > 0 and GetTick() + timeToCollision * 1000 > spellHitTime then
			return true
		end		
	end
	end
	
	return false
end

function VectorObj(pos)
	return Vector(pos.x,0,pos.z or pos.y)
end

function GetTimeToCollision2 (Pa, Pb, Va, Vb, Ra, Rb)
--https://code.google.com/p/xna-circle-collision-detection/downloads/detail?name=Circle%20Collision%20Example.zip&can=2&q=
    local Pab = Vector(Pa - Pb) --no need?
    local Vab = Vector(Va - Vb)
	
    local a = Vab:dotP(Vab)
    local b = 2 * Pab:dotP(Vab)
    local c = Pab:dotP(Pab) - (Ra + Rb) * (Ra + Rb)

    local discriminant = b * b - 4 * a * c
    
	if discriminant >= 0 then
        local t0 = (-b + math.sqrt(discriminant)) / (2 * a)
        local t1 = (-b - math.sqrt(discriminant)) / (2 * a)
		
		return math.min(t1,t0)
	end
		
	return nil
end

function GetTimeToCollision (circle1, circle2, dir1, dir2, radius1, radius2)
--http://compsci.ca/v3/viewtopic.php?t=14897

    local A, B, C, D, DISC

    -- Breaking down the formula for t
    A = dir1.x ^ 2 + dir1.y ^ 2 - 2 * dir1.x * dir2.x + dir2.x ^ 2 - 2 * dir1.y * dir2.y + dir2.y ^ 2 
    B = -circle1.x * dir1.x - circle1.y * dir1.y + dir1.x * circle2.x + dir1.y * circle2.y + circle1.x * dir2.x - 
        circle2.x * dir2.x + circle1.y * dir2.y - circle2.y * dir2.y 
    C = dir1.x ^ 2 + dir1.y ^ 2 - 2 * dir1.x * dir2.x + dir2.x ^ 2 - 2 * dir1.y * dir2.y + dir2.y ^ 2 
    D = circle1.x ^ 2 + circle1.y ^ 2 - radius1 ^ 2 - 2 * circle1.x * circle2.x + circle2.x ^ 2 - 2 * circle1.y * circle2.y + 
        circle2.y ^ 2 - 2 * radius1 * radius2 - radius2 ^ 2 
    DISC = (-2 * B) ^ 2 - 4 * C * D 
	
    --[[ If the discriminent if non negative, a collision will occur and * 
     * we must compare the time to our current time of collision. We   * 
     * udate the time if we find a collision that has occurd earlier   * 
     * than the previous one. ]]
    if DISC >= 0 then 
        -- We want the smallest time */ 
        t = math.min (0.5 * (2 * B - math.sqrt (DISC)) / A, 0.5 * (2 * B + math.sqrt (DISC)) / A) 
    else
		return nil
	end
end

function GetLongestMapPoint(pos, dir)

end

function GetLongestPoint(pos)
	
	local longPos = pos
	
	local dir = (pos - heroPoint):normalized()
	local checkDist = 50
	local checkIndex = 0
	
	while longPos:distance(pos) < 300 do
	
	checkIndex = checkIndex + 1
	local tPos = heroPoint + dir * checkIndex * checkDist	
	
	if IsWall(D3DXVECTOR3(tPos.x, myHero.y, tPos.y)) then
		return longPos
	end
	
	for spellName, spell in pairs(skillShots) do
		if spell.startPos and spell.endPos then
			if inSkillShot(spell, tPos, Menu.DodgeBuffer) then
				return longPos
			end
		end
	end	
	
	longPos = tPos
	end
	
	return longPos
end

function GetBestLastMove()
	if lastMovePos == nil then return nil end

	local bestPosition = nil
	local maxBestPos = 0
	
	local posChecked = 0	
	local posRadius = 10	
	local radiusIndex = 0
	
	--while posChecked < 25 do
	radiusIndex = radiusIndex + 1
	
	local curRadius = 100 --radiusIndex * (2*posRadius)
	local curCircleChecks = math.ceil((2*math.pi*curRadius)/(2*posRadius))
	
	for i=1, curCircleChecks, 1 do
	
	posChecked = posChecked + 1
	local cRadians = (2*math.pi/(curCircleChecks-1))*i
	local pos = Point(myHero.x + curRadius*math.cos(cRadians), myHero.z + curRadius*math.sin(cRadians) )
	local inDanger = false

	local lastMoveDir = (lastMovePos - heroPoint):normalized()
	local curMoveDir = (pos - heroPoint):normalized()
		
	if not IsWall(D3DXVECTOR3(pos.x, myHero.y, pos.y)) then --and pos:distance(lastMovePos) < heroPoint:distance(lastMovePos) then--lastMoveDir:dotP( curMoveDir ) > .5 then
	
	for spellName, spell in pairs(skillShots) do
		if spell.startPos and spell.endPos then
			if inSkillShot(spell, pos, Menu.DodgeBuffer + 25) then
				inDanger = true
				--break
			end
		end
	end
		
	--DrawCircle(pos.x, myHero.y, pos.y, 50, 0x00FF00)
	if not inDanger and maxBestPos < Vector(lastMoveDir.x,0,lastMoveDir.y):dotP(Vector(curMoveDir.x,0,curMoveDir.y)) then
		maxBestPos = Vector(lastMoveDir.x,0,lastMoveDir.y):dotP(Vector(curMoveDir.x,0,curMoveDir.y))
		bestPosition = pos
		
	end
	
	end
	
	end	
	
	--end
	if bestPosition == nil then return nil end
	
	local tPos = GetLongestPoint(bestPosition)
	lastBestLastMove = tPos
	
	return tPos
end

function checkMoveToDirection(x,y)
	local movePos = Point(x,y)	
	
	local dir = (movePos - heroPoint):normalized()
	
	local predPos = heroPoint + dir * (Menu.DodgeBuffer + 100)
	
	for spellName, spell in pairs(skillShots) do					
	if spell.startPos and spell.endPos then
	
	if not playerInSkillShot(spell) and playerDistanceToSpell(spell, predPos) < spell.info.radius + Menu.DodgeBuffer then
		return true			
	end
	end
	end
	
	return false
end

function checkHeroDirection()
	local waypoints = wayPointManager:GetWayPoints(myHero)
	
	if waypoints and #waypoints > 1 then
		local waypoint1 = Vector(waypoints[1].x, 0, waypoints[1].y)
		local waypoint2 = Vector(waypoints[2].x, 0, waypoints[2].y)
		local dir = (waypoint2 - waypoint1):normalized()
		
		local predPos = Vector(myHero.visionPos) + dir * (Menu.DodgeBuffer + 100)
		
		for spellName, spell in pairs(skillShots) do					
		if spell.startPos and spell.endPos then
		
		if playerDistanceToSpell(spell) < spell.info.radius + Menu.DodgeBuffer + 100 then
			return true			
		end
		end
		end
	end
	
	return false
end

function checkHeroDirection2()
	local waypoints = wayPointManager:GetWayPoints(myHero)
	
	if waypoints and #waypoints > 1 then
		local waypoint1 = Vector(waypoints[1].x, 0, waypoints[1].y)
		local waypoint2 = Vector(waypoints[2].x, 0, waypoints[2].y)
		local dir = (waypoint2 - waypoint1):normalized()
		
		local predPos = Vector(myHero.visionPos) + dir * (Menu.DodgeBuffer + 100)
		
		return not canHeroWalkToPos(predPos)
	end
	
	return false
end

--oldMoveTo = _G.player:MoveTo

function GetMyHero()
	for i = 1, heroManager.iCount do
		local hero = heroManager:GetHero(i)
		
		if hero.name == myHero.name then
			return hero
		end
	end
end

--[[
local oldHero = GetMyHero()

function _G.player:MoveTo(x,y) myMoveTo(x,y) end
function _G.myHero:MoveTo(x,y) myMoveTo(x,y) end

function myMoveTo(x,y)

	if dodging then
	if lastBestPosition and lastBestPosition:distance(Point(x,y)) < 5 then
	oldHero:MoveTo(x,y)
	end
	
	else
	if not checkMoveToDirection(x,y) then
	oldHero:MoveTo(x,y)
	else
		local pos = GetBestLastMove()
		if pos then
		lastBestLastMove = pos
		oldHero:MoveTo(pos.x, pos.y)	
		lastEvadePos = pos
		end
	end
	end

end]]

function CreateLinearTestSkillShot(spellDelay, radius, projectileSpeed)
	local spellInfo = {charName = myHero.charName, name = 'test', spellName = 'test', spellDelay = spellDelay, projectileSpeed = projectileSpeed, range = 1000, radius = radius, type = "LINE"}
	skillShots['test'] = {startTime = GetTick(), endTime = math.huge, startPos = Vector(myHero.visionPos), endPos = Vector(mousePos), info = spellInfo, charIndex = 1}
end

function CreateLinearTestAtMouse()
	CreateLinearTestSkillShot(350, 100, 1300)
end

function DevTest()
	if Menu.Dev.CreateLinearTest then
	CreateLinearTestAtMouse()
	Menu.Dev.CreateLinearTest = false
	end
end

function DodgeSkillShots()
	local playerInDanger = false
	local bestPosInDanger = false
	local playerWalkInDanger = false
	
	--reach bestPosYet?
		
	local waypoints = wayPointManager:GetWayPoints(myHero)	
	if #waypoints < 2 then
		lastMovePos = nil
	end
	
	for spellName, spell in pairs(skillShots) do
					
		if spell.startPos and spell.endPos then
				
		if playerInSkillShot(spell) then --and not canHeroEvade(myHero, Menu.DodgeBuffer, spell) then 
			playerInDanger = true			
		end
		
		if lastBestPosition and inSkillShot(spell, Point(lastBestPosition.x, lastBestPosition.y), Menu.DodgeBuffer) then
			bestPosInDanger = true
		end
		
		end
	end
	
	if bestPosInDanger then --and lastBestPosition:distance(heroPoint) < 10 then
		lastBestPosition = GetBestPosition()
	end
	
	if isDodging and lastBestPosition == nil then
		lastBestPosition = GetBestPosition()
	end	

	--[[
	if Menu.DangerArea and checkHeroDirection() and isDodging == false then
		local pos = GetBestLastMove()
		if pos then
			--Packet('S_MOVE', {x = pos.x, y = pos.y}):send()
			lastBestLastMove = pos
			myHero:MoveTo(pos.x, pos.y)	
			lastEvadePos = pos
		else
			--myHero:HoldPosition()
		end
	end]]
	
	if isDodging == false and playerInDanger then
		isDodging = true
	elseif not playerInDanger then
		isDodging = false
	end
	
	if lastBestPosition then
	if isDodging then		
		local pos = lastBestPosition		
		lastMoveCommand = GetTick()
		lastEvadePos = pos
		myHero:MoveTo(pos.x, pos.y)		
		
		if GetTick() - lastMoveCommand > 25 then
		Packet('S_MOVE', {x = pos.x, y = pos.y}):send()
		end
	elseif isDodging and lastBestPosition:distance(heroPoint) < 10 then
		isDodging = false
	end
	
	else
	
	--[[
	if playerInDanger then
	lastBestPosition = GetBestPosition()
	end]]
	
	end
end

function OnTick()
	heroPoint = Point(myHero.visionPos.x, myHero.visionPos.z)

	if Menu.StopDodging == false and Menu.DodgeSkillShots and GetTick() - lastTickCount > Menu.TickLimiter and myHero.dead == false then
		
	if Menu.CheckUnitCollision then
	allMinions:update()
	end	
	
	DodgeSkillShots()
	lastTickCount = GetTick()
	end
	
end

function PrintText (...)
	local topOffset = 150
	local leftOffset = 100
	local fontSize = 20
	
	local t, len = {}, select("#",...)
    for i=1, len do
        local v = select(i,...)
        local _type = type(v)
        if _type == "string" then t[i] = v
        elseif _type == "number" then t[i] = tostring(v)
        elseif _type == "table" then t[i] = table.serialize(v)
        elseif _type == "boolean" then t[i] = v and "true" or "false"
        elseif _type == "userdata" then t[i] = ctostring(v)
        else t[i] = _type
        end
    end
    if len>0 then 	
	DrawText(table.concat(t), fontSize, leftOffset, topOffset, 0xFF00FF00)
	end
end	

local printTime = 0
function OnDraw()

	if Menu.DrawVisionPos then
		DrawCircle(myHero.visionPos.x, myHero.visionPos.y, myHero.visionPos.z, 50, 0x00FF00)				
	end

	if Menu.DrawSkillShots then		
				
		for spellName, spell in pairs(skillShots) do
									
			if spell.startPos and spell.endPos and not CheckCasterDead(spell) then
				
			if spell.info.type == "LINE" then				
			
				if spell.spellParticle then
				
				--DrawCircle(spell.spellParticle.x, spell.spellParticle.y, spell.spellParticle.z, GetSpellRadius(spell), 0x00FF00)				
				
				local dir = (Vector(spell.endPos.x,0,spell.endPos.z) - Vector(spell.startPos.x,0,spell.startPos.z)):normalized()
				local spellPos = Vector(spell.startPos) + dir * GetDistance(spell.spellParticle, spell.startPos)
				
				DrawLine3D(spellPos.x, spellPos.y, spellPos.z, spell.endPos.x, spell.endPos.y, spell.endPos.z, GetSpellRadius(spell), ARGB(125, 125, 0, 0))
				else
				DrawLine3D(spell.startPos.x, spell.startPos.y, spell.startPos.z, spell.endPos.x, spell.endPos.y, spell.endPos.z, GetSpellRadius(spell), ARGB(125, 125, 0, 0))
				end
			else
                DrawCircle(spell.endPos.x, spell.endPos.y, spell.endPos.z, GetSpellRadius(spell), 0x00FF00)
            end
			
			end			
		end
		
		--DevTest()
		--PrintText(isDodging)
		--[[
		if true then --GetTick() - printTime > 100 then
		
		local waypoints = wayPointManager:GetWayPoints(myHero)	
		if #waypoints < 2 then
			canHeroWalkToPos(heroPoint)
		else
			canHeroWalkToPos(waypoints[2])
		end		
		printTime = GetTick()
		end]]
	end
end

--spatial error
function OnSendPacket(p)
	
	if Menu.StopDodging == false and Menu.DodgeSkillShots and Menu.BlockMovement and myHero.dead == false then
	
	packet = Packet(p)
	packetName = Packet(p):get('name')
	
	--[[
	if packetName == 'S_MOVE' and packet:get('type') == 2 then
		packet:block()
	end]]

	if packetName == 'S_MOVE' and packet:get('type') == 2 then
				
		local movePos = Point(packet:get('x'),packet:get('y'))		
		if lastEvadePos and lastEvadePos:distance(movePos) > 50
			and GetTick() - lastUserMoveCommand > 100 then
		lastMovePos = movePos
		lastUserMoveCommand = GetTick()
		
		--if lastMovePos == nil or lastMovePos:distance(movePos) > 10 then
		
		if isDodging == false then
		local bestPos = GetBestPosition()		
		if bestPos and lastBestPosition and bestPos:distance(lastBestPosition) > 100 then
		lastBestPosition = bestPos
		end
		end
		
		--end
		
		end
	end
	
	if Menu.DangerArea and packetName == 'S_MOVE' and isDodging == false and packet:get('type') == 2 then
		local movePos = Point(packet:get('x'),packet:get('y'))
		
		if GetTick() - lastBlockedTime < 100 and lastBlockedPos and lastBlockedPos:distance(movePos) < 10 then
			packet:block()
			return
		end
		
		if lastEvadePos and lastEvadePos:distance(movePos) < 50 then
			return
		end
		
		if checkMoveToDirection(movePos.x, movePos.y) then
			packet:block()
			
			lastBlockedPos = movePos
			lastBlockedTime = GetTick()
				
			local pos = GetBestPosition() --lastBestPosition
			if pos then
				lastMoveCommand = GetTick()
				Packet('S_MOVE', {x = pos.x, y = pos.y}):send()
				lastEvadePos = pos
			end
			return
		end
	end
	
	if isDodging then
		if packetName == 'S_MOVE' then
			local movePos = Point(packet:get('x'),packet:get('y'))
			if lastBestPosition and lastBestPosition:distance(movePos) > 5 then --test 50
			packet:block()
			myHero:MoveTo(lastBestPosition.x, lastBestPosition.y)
			lastEvadePos = lastBestPosition
			end
		elseif packetName == 'S_CAST' then
			packet:block()	
		end
		
		return
	end

	
	
	end
end

function OnLoad()
	PrintChat("<font color=\"#3F92D2\" >ezEvade v" .. sVersion .. " Loaded</font>")
	
	DelayAction(CheckForUpdates, math.random(1,5), {})
	
	Menu = scriptConfig("ezEvade v" .. sVersion, "ezEvade" .. sVersion)
	Menu:addParam("DrawSkillShots", "Draw Skillshots", SCRIPT_PARAM_ONOFF, true)
	Menu:addParam("DrawVisionPos", "Draw Hero Position", SCRIPT_PARAM_ONOFF, false)
	Menu:addParam("DodgeSkillShots", "Dodge Skillshots", SCRIPT_PARAM_ONOFF, true)
	Menu:addParam("StopDodging", "Stop Dodging Key", SCRIPT_PARAM_ONKEYDOWN, false, 16)
	Menu:addParam("DangerArea", "Stop moving into Danger Area", SCRIPT_PARAM_ONOFF, true)
	Menu:addParam("BlockMovement", "Block Movement", SCRIPT_PARAM_ONOFF, true)
	Menu:addParam("UnitCollision", "Check Unit Collision", SCRIPT_PARAM_ONOFF, false)
	Menu:addParam("CheckWall", "Check Wall Collision", SCRIPT_PARAM_ONOFF, true)
	Menu:addParam("Undodgeable", "Ignore Undodgeable Spells", SCRIPT_PARAM_ONOFF, true)
		
	Menu:addParam("DodgeBuffer", "Dodge Buffer", SCRIPT_PARAM_SLICE, 100, 0, 500, 0)
	Menu:addParam("TickLimiter", "Limit CPU Ticks", SCRIPT_PARAM_SLICE, 0, 0, 200, 0)
	
	--[[
	if GetUser() == '' then
		Menu:addSubMenu("Dev Tab", "Dev")
		Menu.Dev:addParam("CreateLinearTest", "Create Linear Spell", SCRIPT_PARAM_ONKEYTOGGLE, false, string.byte("i"))
	end]]
	
	Menu:addSubMenu("Skill Settings", "SkillSettings")
	
	local championInfo = evadeLib:GetChampionInfo()	
	local spellKeyStr = { [_Q] = "Q", [_W] = "W", [_E] = "E", [_R] = "R" } 
	
	for i = 1, heroManager.iCount do
		local hero = heroManager:GetHero(i)
		
		if hero.team ~= player.team then
			EnemyIndex[hero.charName] = i
					
			if championInfo[hero.charName] and championInfo[hero.charName].skillshots then
				
				Menu.SkillSettings:addSubMenu(hero.charName,hero.charName .. "SkillSettings")
				
				for spellName, spell in pairs(championInfo[hero.charName].skillshots) do
					spell.charName = hero.charName
					
					if spell.spellDelay == nil then
						spell.spellDelay = 250
					end
					
					procSkillShots[spell.spellName] = spell					
					
					if spell.projectileName then
					objSkillShots[spell.projectileName] = spell
					end
					
					local spellDefaultOption = true
					if spell.type == "CIRCULAR" then
						spellDefaultOption = false
					end
					
					Menu.SkillSettings[hero.charName .. "SkillSettings"]:addParam(spell.spellName, spell.name, SCRIPT_PARAM_ONOFF, spellDefaultOption)
					Menu.SkillSettings[hero.charName .. "SkillSettings"]:addParam(spell.spellName .. "buffer", "Spell Radius", SCRIPT_PARAM_SLICE, spell.radius, 0, spell.radius + 150, 0)
				end
			end						
		else
			AllyIndex[hero.charName] = i
		end
	end
end

function GetTick()
	return GetGameTimer() * 1000
end

----------------AUTO Update------------------------------

function DownloadScript (scriptName, scriptVersion, url, scriptPath)
	local UPDATE_TMP_FILE = LIB_PATH.. scriptName .. "Tmp.txt"
	
	DownloadFile(url, UPDATE_TMP_FILE, 
		function ()
		
		file = io.open(UPDATE_TMP_FILE, "rb")
		if file ~= nil then
        downloadContent = file:read("*all")
        file:close()
        os.remove(UPDATE_TMP_FILE)
		end
		
	
	if downloadContent then
		
		file = io.open(scriptPath, "w")
        
		if file then
            file:write(downloadContent)
            file:flush()
            file:close()
            print("Successfully updated " .. scriptName .. " to Version " .. scriptVersion)
			print("Please press F9 to reload script.")
        else
            print("Error updating!")
        end
		
	end
	
		
	end)	
end

function ReadLastUpdateTime ()

	local updateTimeFile = LIB_PATH.."ezEvadeUpdateTime"
	
	file = io.open(updateTimeFile, "rb")
	if file ~= nil then
    content = file:read("*all")
    file:close()
	
	return tonumber(content)
	end
	
	return 0
end

function WriteLastUpdateTime ()
	local updateTimeFile = LIB_PATH.."ezEvadeUpdateTime"
	
	file = io.open(updateTimeFile, "w")
     
	if file then
        file:write(os.time())
        file:flush()
        file:close()
    end
end

function CheckForUpdates ()

	local lastUpdateTime = ReadLastUpdateTime()
			
	if true then
	--if os.time()-lastUpdateTime > 86400 and os.time() > lastUpdateTime then --a day has passed

	local URL = "https://bitbucket.org/Xgs/bol/raw/master/Versions.txt"
	local UPDATE_TMP_FILE = LIB_PATH.."TmpVersions.txt"
	
	DownloadFile(URL, UPDATE_TMP_FILE, 
	function ()
		file = io.open(UPDATE_TMP_FILE, "rb")
		if file ~= nil then
        versionTextContent = file:read("*all")
        file:close()
        os.remove(UPDATE_TMP_FILE)
		end
	
	if versionTextContent then		
		local url = "https://bitbucket.org/Xgs/bol/raw/master/ezEvade.lua"
		Update(versionTextContent, "ezEvade", sVersion, url, SCRIPT_PATH.."ezEvade.lua")
	end
		
	end)
	
	WriteLastUpdateTime()
	end
	
end
	
function Update(versionText, scriptName, scriptVersion, url, scriptPath)
	local content = versionText
	
	--print("Checking updates for " .. scriptName .. "...")
	
    if content then		
        tmp, sstart = string.find(content, "\"" .. scriptName .. "\" : \"")
        if sstart then
            send, tmp = string.find(content, "\"", sstart+1)
        end
		
        if send then
            Version = tonumber(string.sub(content, sstart+1, send-1))
        end
		
		if (Version ~= nil) and (Version > scriptVersion) then 
		
		print("Found update for " .. scriptName .. ", downloading...")
		DelayAction(DownloadScript,2,{scriptName, Version, url, scriptPath})
		
			
        elseif (Version ~= nil) and (Version <= scriptVersion) then
            --print("No updates found. Latest Version: " .. Version)
        end
    end
end
----------------AUTO Update------------------------------