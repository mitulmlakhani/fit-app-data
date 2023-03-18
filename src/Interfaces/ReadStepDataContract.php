<?php

namespace Mitulmlakhani\FitAppData\Interfaces;

interface ReadStepDataContract
{
    public function getStepsCount($startTime, $endTime = null, $bucketTime = null): array;
}
